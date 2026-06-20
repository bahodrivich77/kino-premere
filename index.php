<?php

ini_set('display_errors', true);

// ========================================================
// 1) BU YERGA O'ZINGIZNING MA'LUMOTLARINGIZNI YOZING
// ========================================================

// BotFather'dan olingan token (https://t.me/BotFather -> /newbot)
// MUHIM: eski tokenni revoke qilgan bo'lsangiz, shu yerga YANGI tokenni yozing!
define('API_KEY', 'BU_YERGA_TOKENINGIZNI_YOZING');

// Sizning shaxsiy Telegram ID raqamingiz (bot egasi).
// Bilish uchun: Telegram'da @userinfobot ga /start yozing.
$umidjon = 0000000000; // <-- shu yerga O'Z ID raqamingizni yozing
$owners = array($umidjon);

// Kino kodlari joylanadigan kanal username'i (@ belgisisiz)
$user = "mening_kanal_username"; // <-- shu yerga o'zgartiring (bu shunchaki reklama matnidagi {admin} uchun)

// InfinityFree panelidan olingan MySQL ma'lumotlari
define('DB_HOST', 'sql208.infinityfree.com');
define('DB_USER', 'if0_42224262');
define('DB_PASS', 'k1QzU1h8jK9W0c6');
define('DB_NAME', 'if0_42224262_kinobot');

// ========================================================
// BUNDAN PASTINI O'ZGARTIRISH SHART EMAS
// ========================================================

$idbot = 0;
$connect = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if(!$connect){
    file_put_contents("db_error.log", date('Y-m-d H:i:s')." ".mysqli_connect_error()."\n", FILE_APPEND);
}
if($connect) mysqli_set_charset($connect, 'utf8mb4');

function bot($method,$datas=[]){
	$url = "https://api.telegram.org/bot". API_KEY ."/". $method;
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
	curl_setopt($ch,CURLOPT_TIMEOUT,15);
	$res = curl_exec($ch);
	if(curl_error($ch)){
		file_put_contents("curl_error.log", date('Y-m-d H:i:s')." ".curl_error($ch)."\n", FILE_APPEND);
		return json_decode('{"ok":false}');
	}
	return json_decode($res);
}

$bot = bot('getMe')->result->username ?? '';

#================================================

function deleteFolder($path){
if(is_dir($path) === true){
$files = array_diff(scandir($path), array('.', '..'));
foreach ($files as $file)
deleteFolder(realpath($path) . '/' . $file);
return rmdir($path);
}else if (is_file($path) === true)
return unlink($path);
return false;
}

function sendMessage($id, $text, $key = null){
return bot('sendMessage',[
'chat_id'=>$id,
'text'=>$text,
'parse_mode'=>'html',
'disable_web_page_preview'=>true,
'reply_markup'=>$key
]);
}

function editMessageText($cid, $mid, $text, $key = null){
return bot('editMessageText',[
'chat_id'=>$cid,
'message_id'=>$mid,
'text'=>$text,
'parse_mode'=>'html',
'disable_web_page_preview'=>true,
'reply_markup'=>$key
]);
}

function sendVideo($cid, $f_id, $text, $key = null){
return bot('sendVideo',[
'chat_id'=>$cid,
'video'=>$f_id,
'caption'=>$text,
'parse_mode'=>'html',
'reply_markup'=>$key
]);
}

function sendPhoto($cid, $f_id, $text, $key = null){
return bot('sendPhoto',[
'chat_id'=>$cid,
'photo'=>$f_id,
'caption'=>$text,
'parse_mode'=>'html',
'reply_markup'=>$key
]);
}

function copyMessage($id, $from_chat_id, $message_id){
return bot('copyMessage',[
'chat_id'=>$id,
'from_chat_id'=>$from_chat_id,
'message_id'=>$message_id
]);
}

function forwardMessage($id, $cid, $mid){
return bot('forwardMessage',[
'from_chat_id'=>$id,
'chat_id'=>$cid,
'message_id'=>$mid
]);
}

function deleteMessage($cid,$mid){
return bot('deleteMessage',[
'chat_id'=>$cid,
'message_id'=>$mid
]);
}

function getChatMember($cid, $userid){
return bot('getChatMember',[
'chat_id'=>$cid,
'user_id'=>$userid
]);
}

function replyKeyboard($key){
return json_encode(['keyboard'=>$key, 'resize_keyboard'=>true]);
}

function getName($id){
$getname = bot('getchat',['chat_id'=>$id])->result->first_name ?? null;
if(!empty($getname)){
return $getname;
}else{
return bot('getchat',['chat_id'=>$id])->result->title ?? '';
}
}

function getAdmin($chat){
$url = "https://api.telegram.org/bot".API_KEY."/getChatAdministrators?chat_id=$chat";
$result = @file_get_contents($url);
if($result === false) return false;
$result = json_decode($result);
return $result->ok ?? false;
}

function joinchat($id){
$array = array("inline_keyboard"=>[]);
if(!file_exists("admin/kanal.txt")) return true;
$kanallar = trim(file_get_contents("admin/kanal.txt"));
if($kanallar == ""){
return true;
}
$ex = array_filter(explode("\n",$kanallar));
$ex = array_values($ex);
$uns = false;
foreach($ex as $i => $first_line){
$first_line = trim($first_line);
if($first_line == "") continue;
$url = file_exists("admin/links/$first_line") ? file_get_contents("admin/links/$first_line") : "";
$chatInfo = bot('getChat',['chat_id'=>$first_line]);
$ism = $chatInfo->result->title ?? $first_line;
$ret = bot("getChatMember",[
"chat_id"=>$first_line,
"user_id"=>$id,
]);
$stat = $ret->result->status ?? null;
if(!$stat){
continue; // bot kanalda admin bo'lmasa yoki xato bo'lsa, tekshirmasdan o'tkazib yuboramiz
}
if($stat == "left"){
$get = file_exists("admin/zayavka/$first_line") ? file_get_contents("admin/zayavka/$first_line") : "";
if(mb_stripos($get,$id)!==false){
$stat = "member";
}
}
if($stat=="creator" or $stat=="administrator" or $stat=="member"){
$array['inline_keyboard'][][0] = ['text'=>"✅ ". $ism, 'url'=>$url];
}else{
$array['inline_keyboard'][][0] = ['text'=>"❌ ". $ism, 'url'=>$url];
$uns = true;
}
}
$array['inline_keyboard'][][0] = ['text'=>"✅ Tekshirish", 'callback_data'=>"check"];
if($uns == true){
sendMessage($id, "❌ <b>Botdan to'liq foydalanish uchun quyidagi kanallarimizga obuna bo'ling!</b>", json_encode($array));
return false;
}
return true;
}

#================================================

date_default_timezone_set('Asia/Tashkent');
$soat = date('H:i');
$sana = date("d.m.Y");

#================================================

$raw_input = file_get_contents('php://input');
file_put_contents("debug.log", date('Y-m-d H:i:s')." RAW: ".$raw_input."\n", FILE_APPEND);
$update = json_decode($raw_input);
if(json_last_error() !== JSON_ERROR_NONE){
file_put_contents("debug.log", date('Y-m-d H:i:s')." JSON ERROR: ".json_last_error_msg()."\n", FILE_APPEND);
}

$message = $update->message ?? null;
$callback = $update->callback_query ?? null;

$cid=null;$Tc=null;$text=null;$mid=null;$from_id=null;$name=null;$last=null;
$photo=null;$video=null;$file_id=null;$file_name=null;$file_size=null;$size=null;$dtype=null;
$audio=null;$voice=null;$sticker=null;$video_note=null;$animation=null;$caption=null;
$data=null;$qid=null;

if (isset($message)) {
$cid = $message->chat->id;
$Tc = $message->chat->type;

$text = $message->text ?? null;
$mid = $message->message_id;

$from_id = $message->from->id;
$name = $message->from->first_name ?? '';
$last = $message->from->last_name ?? '';

if(isset($message->photo)) $photo = end($message->photo)->file_id;

if(isset($message->video)){
$video = $message->video;
$file_id = $video->file_id;
$file_name = $video->file_name ?? ('video_'.time());
$file_size = $video->file_size ?? 0;
$size = $file_size/1000;
$dtype = $video->mime_type ?? '';
}

$audio = $message->audio->file_id ?? null;
$voice = $message->voice->file_id ?? null;
$sticker = $message->sticker->file_id ?? null;
$video_note = $message->video_note->file_id ?? null;
$animation = $message->animation->file_id ?? null;

$caption = $message->caption ?? null;
}

if (isset($callback)) {
$data = $callback->data ?? null;
$qid = $callback->id;

$cid = $callback->message->chat->id;
$Tc = $callback->message->chat->type;
$mid = $callback->message->message_id;

$from_id = $callback->from->id;
$name = $callback->from->first_name ?? '';
$last = $callback->from->last_name ?? '';
}

// chat_join_request alohida turdagi update, asosiy oqimdan tashqarida ishlanadi
if(isset($update->chat_join_request)){
$joinchatid = $update->chat_join_request->chat->id;
$qb = $update->chat_join_request->from->id;
$ty = $update->chat_join_request->chat->type;
if($ty == "channel" or $ty == "supergroup"){
if(!is_dir("admin/zayavka")) @mkdir("admin/zayavka", 0777, true);
$get = file_exists("admin/zayavka/$joinchatid") ? file_get_contents("admin/zayavka/$joinchatid") : "";
if(mb_stripos($get,$qb)===false){
file_put_contents("admin/zayavka/$joinchatid", "$get\n$qb");
}
}
exit();
}

// bot guruh/kanaldan chiqarib yuborilganda
if(isset($update->my_chat_member)){
$botdel = $update->my_chat_member->new_chat_member ?? null;
$botdelid = $update->my_chat_member->from->id ?? null;
$userstatus = $botdel->status ?? null;
if($userstatus == "kicked" and $botdelid){
$stmt = mysqli_prepare($connect,"UPDATE user_id SET sana = 'tark' WHERE id = ?");
mysqli_stmt_bind_param($stmt,"s",$botdelid);
mysqli_stmt_execute($stmt);
}
exit();
}

if($cid === null) exit(); // boshqa noma'lum update turlari

#=================================================

if(!is_dir("admin")) mkdir("admin");
if(!is_dir("admin/links")) mkdir("admin/links");
if(!is_dir("admin/zayavka")) mkdir("admin/zayavka");
if(!file_exists("admin/kino.txt")) file_put_contents("admin/kino.txt","");
if(!file_exists("admin/rek.txt")) file_put_contents("admin/rek.txt","🎬 @%kino% kanali uchun maxsus joylandi!\nAdmin: @%admin%");
if(!file_exists("admin/admins.txt")) file_put_contents("admin/admins.txt","");
if(!file_exists("admin/kanal.txt")) file_put_contents("admin/kanal.txt","");

$kino_id = trim(file_get_contents("admin/kino.txt"));
$kino = '';
if($kino_id != ''){
$kc = bot('getchat',['chat_id'=>$kino_id]);
$kino = $kc->result->username ?? '';
}
$reklama = str_replace(["%kino%","%admin%"],[$kino,$user], file_get_contents("admin/rek.txt"));

#================================================

$admins_raw = file_get_contents("admin/admins.txt");
$admins = array_filter(array_map('trim', explode("\n", $admins_raw)));
$admin = array_merge($owners, $admins);
// solishtirishda tip muammosi bo'lmasligi uchun barchasini string qilamiz
$admin = array_map('strval', $admin);

#=================================================

$stmt = mysqli_prepare($connect, "SELECT * FROM `user_id` WHERE `id` = ?");
mysqli_stmt_bind_param($stmt, "s", $cid);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$step = $user['step'] ?? '0';
$ban = $user['ban'] ?? 0;
$lastmsg = $user['lastmsg'] ?? '';

#=================================================

if ($ban == 1) exit();

mysqli_query($connect,"CREATE TABLE IF NOT EXISTS data(
`id` int(20) auto_increment primary key,
`file_name` varchar(256),
`file_id` varchar(256),
`film_name` varchar(256),
`film_date` varchar(256)
)");

mysqli_query($connect,"CREATE TABLE IF NOT EXISTS settings(
`id` int(20) auto_increment primary key,
`kino` varchar(256),
`kino2` varchar(256)
)");

mysqli_query($connect,"CREATE TABLE IF NOT EXISTS user_id(
`uid` int(20) auto_increment primary key,
`id` varchar(256),
`step` varchar(256),
`ban` varchar(256),
`lastmsg` varchar(256),
`sana` varchar(256)
)");

mysqli_query($connect,"CREATE TABLE IF NOT EXISTS texts(
`id` int(20) auto_increment primary key,
`start` varchar(1024)
)");

if(!mysqli_fetch_assoc(mysqli_query($connect,"SELECT * FROM texts WHERE id=1"))){
mysqli_query($connect,"INSERT INTO `texts`(`id`, `start`) VALUES ('1','8J+RiyBBc3NhbG9tdSBhbGF5a3VtIHtuYW1lfSAgYm90aW1pemdhIHh1c2gga2VsaWJzaXouCgrinI3wn4+7IEtpbm8ga29kaW5pIHl1Ym9yaW5nLg==')");
}

if($Tc == "private"){
if($user){
$s = "$sana | $soat";
$stmt = mysqli_prepare($connect,"UPDATE user_id SET sana = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, "ss", $s, $cid);
mysqli_stmt_execute($stmt);
}else{
$st='0'; $s = "$sana | $soat"; $b='0';
$stmt = mysqli_prepare($connect,"INSERT INTO `user_id`(`id`,`step`,`sana`,`ban`) VALUES (?,?,?,?)");
mysqli_stmt_bind_param($stmt, "ssss", $cid, $st, $s, $b);
mysqli_stmt_execute($stmt);
}
}

if(!mysqli_fetch_assoc(mysqli_query($connect,"SELECT * FROM `settings`"))){
mysqli_query($connect,"INSERT INTO `settings`(`kino`,`kino2`) VALUES ('0','0')");
}

#=================================================

$panel = replyKeyboard([
[['text'=>"📊 Statistika"]],
[['text'=>"🎬 Kino qo'shish"],['text'=>"🗑️ Kino o'chirish"]],
[['text'=>"👨‍💼 Adminlar"],['text'=>"💬 Kanallar"]],
[['text'=>"🔴 Blocklash"],['text'=>"🟢 Blockdan olish"]],
[['text'=>"✍️ Post xabar"],['text'=>"📬 Forward xabar"]],
[['text'=>"⬇️ Panelni Yopish"]],
]);

$cancel = replyKeyboard([
[['text'=>"◀️ Orqaga"]]
]);

$kanallar_p = replyKeyboard([
[['text'=>"🔷 Kanal ulash"],['text'=>"🔶 Kanal uzish"]],
[['text'=>"💡 Kino kanal"],['text'=>"📈 Reklama"]],
[['text'=>"🟩 Majburish a'zolik"]],
[['text'=>"◀️ Orqaga"]]
]);

$removeKey = json_encode(['remove_keyboard'=>true]);

function esc($connect, $v){ return mysqli_real_escape_string($connect, (string)$v); }

#=================================================
# /start
#=================================================

if($text == "/start" and joinchat($cid)==true){
$keyBot = json_encode(['inline_keyboard'=>[
[['text'=>"🔎 Kodlarni qidirish",'url'=>"https://t.me/$kino"]]
]]);
$setting = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM texts WHERE id = 1"));
$start = str_replace(["{name}","{time}"],["<a href='tg://user?id=$cid'>$name</a>","$sana | $soat"],base64_decode($setting['start']));
sendMessage($cid, $start, $keyBot);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'start', `step`='0' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if ($data == "check"){
deleteMessage($cid, $mid);
$keyBot = json_encode(['inline_keyboard'=>[
[['text'=>"🔎 Kodlarni qidirish",'url'=>"https://t.me/$kino"]]
]]);
if (joinchat($cid)==true) {
$setting = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM texts WHERE id = 1"));
$start = str_replace(["{name}","{time}"],["<a href='tg://user?id=$cid'>$name</a>","$sana | $soat"],base64_decode($setting['start']));
sendMessage($cid, $start, $keyBot);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'start', `step`='0' WHERE `id` = ".esc($connect,$cid));
}
exit();
}

#=================================================
# /dev , /help
#=================================================

else if($text == "/dev" and joinchat($cid)==true){
$keyBot = json_encode(['inline_keyboard'=>[
[['text'=>"👨‍💻 Bot dasturchisi",'url'=>"https://t.me/alimov_ak"]],
]]);
sendMessage($cid, "👨‍💻 <b>Botimiz dasturchisi: @alimov_ak</b>", $keyBot);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'start', `step`='0' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if($text == "/help" and joinchat($cid)==true){
$keyBot = json_encode(['inline_keyboard'=>[
[['text'=>"🔎 Kino kodlarini qidirish",'url'=>"https://t.me/$kino"]]
]]);
sendMessage($cid, "<b>📊 Botimiz buyruqlari:</b>\n/start - Botni yangilash ♻️\n/rand - Tasodifiy film 🍿\n/dev - Bot dasturchisi 👨‍💻\n/help - Bot buyruqlari 🔁\n\n<b>🤖 Kinoni yuklash uchun kino kodini yuboring.</b>", $keyBot);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'start', `step`='0' WHERE `id` = ".esc($connect,$cid));
exit();
}

#=================================================
# Admin panel ochish/yopish
#=================================================

else if(($text == "/panel" or $text == "/a" or $text == "/admin" or $text == "/p" or $text == "◀️ Orqaga") and in_array((string)$cid,$admin)){
sendMessage($cid, "<b>👨🏻‍💻 Boshqaruv paneliga xush kelibsiz.</b>\n\n<i>Nimani o'zgartiramiz?</i>", $panel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'panel', `step`='0' WHERE `id` = ".esc($connect,$cid));
@unlink("film.txt");
exit();
}

else if ($text == "⬇️ Panelni Yopish" and in_array((string)$cid,$admin)){
sendMessage($cid, "<b>🚪 Panelni tark etdingiz. Qayta kirish uchun /panel yoki /admin yuboring.\n\nYangilash: /start</b>", $removeKey);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'start', `step`='0' WHERE `id` = ".esc($connect,$cid));
exit();
}

#=================================================
# Kino qo'shish oqimi
#=================================================

else if ($text == "🎬 Kino qo'shish" and in_array((string)$cid,$admin)){
sendMessage($cid, "<b>🎬 Kinoni yuboring:</b>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'movie' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if(isset($video) and $step == "movie"){
file_put_contents("file.id",$file_id);
file_put_contents("file.name",base64_encode($file_name));
sendMessage($cid, "<b>🎬 Kinoni malumotini (nomini) yuboring:</b>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'caption' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if($step == "caption" and $text){
file_put_contents("film.caption",base64_encode($text));
$keyBot = json_encode(['inline_keyboard'=>[
[['text'=>"🎞️ Kanalga yuborish",'callback_data'=>"channel"]]
]]);
$saved_file_id = file_get_contents("file.id");
sendVideo($cid, $saved_file_id, "<b>$text</b>\n\n<b>$reklama</b>",$keyBot);
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if($data == "channel"){
deleteMessage($cid,$mid);
sendMessage($cid, "<b>📝 Post uchun video yoki rasm yuboring:</b>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'post' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if($step == "post"){
$keyBot = json_encode(['inline_keyboard'=>[
[['text'=>"✅ Yuborish",'callback_data'=>"sms"]]
]]);
if($video){
file_put_contents("post.video",$file_id);
file_put_contents("post.type","video");
sendVideo($cid, $file_id,"<b>✅ Qabul qilindi.</b>",$keyBot);
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = ".esc($connect,$cid));
}elseif ($photo){
file_put_contents("post.photo",$photo);
file_put_contents("post.type","photo");
sendPhoto($cid, $photo,"<b>✅ Qabul qilindi.</b>",$keyBot);
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = ".esc($connect,$cid));
}else{
sendMessage($cid, "<b>⚠️ Hatolik yuzberdi video yoki rasm yuboring!</b>",null);
}
exit();
}

else if($data == "sms"){
$saved_file_id = file_get_contents("file.id");
$saved_file_name = file_get_contents("file.name");
$saved_caption = file_get_contents("film.caption");
$settingsRow = mysqli_fetch_assoc(mysqli_query($connect,"SELECT * FROM `settings` WHERE `id` = '1'"));
$code = intval($settingsRow['kino']) + 1;

$stmt = mysqli_prepare($connect,"INSERT INTO data (`id`,`file_name`,`file_id`,`film_name`,`film_date`) VALUES (?,?,?,?,?)");
mysqli_stmt_bind_param($stmt,"sssss",$code,$saved_file_name,$saved_file_id,$saved_caption,$sana);
$save = mysqli_stmt_execute($stmt);

mysqli_query($connect,"UPDATE settings SET kino = '$code' WHERE id = 1");

if($save){
$type = file_get_contents("post.type");

if($type == "video"){
$post_video = file_get_contents("post.video");
$sent = sendVideo("@$kino",$post_video,"🎬 <b>Kino kodi:</b> <code>$code</code>\n\n✅ <b>Ushbu kino botga to'liq holda joylandi!</b>\n\n📎 Bot manzili: @$bot",null);
$mes = $sent->result->message_id ?? null;
if($mes){
deleteMessage($cid,$mid);
sendMessage($cid,"✅ <b>@$kino kanaliga yuborildi!\n\n🔢 Kino kodi: <code>$code</code>\n\n👀 <a href='https://t.me/$kino/$mes'>Ko'rish</a></b>",$panel);
}else{
sendMessage($cid, "<b>⚠️ Kanalga post yuborishda hatolik yuzberdi!</b>",$panel);
}
}elseif ($type == "photo"){
$post_photo = file_get_contents("post.photo");
$sent = sendPhoto("@$kino",$post_photo,"🎬 <b>Kino kodi:</b> <code>$code</code>\n\n✅ <b>Kino botga joylandi. Kodni botga yuboring.</b>\n\n📎 Bot manzili: @$bot",null);
$mes = $sent->result->message_id ?? null;
if($mes){
deleteMessage($cid,$mid);
sendMessage($cid,"✅ <b>@$kino kanaliga yuborildi!\n\n🎬 Kino kodi: <code>$code</code>\n\n👀 <a href='https://t.me/$kino/$mes'>Ko'rish</a></b>",$panel);
}else{
sendMessage($cid, "<b>⚠️ Kanalga post yuborishda hatolik yuzberdi!</b>",$panel);
}
}
@unlink("file.id"); @unlink("file.name"); @unlink("film.caption");
@unlink("post.type"); @unlink("post.video"); @unlink("post.photo");
}else{
sendMessage($cid, "<b>⚠️ Kinoni bazaga saqlashda hatolik yuzberdi!</b>",$panel);
}

mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = ".esc($connect,$cid));
exit();
}

#=================================================
# Kino o'chirish
#=================================================

else if ($text == "🗑️ Kino o'chirish" and in_array((string)$cid,$admin)){
sendMessage($cid, "<b>🗑️ Kino o'chirish uchun menga kino kodini yuboring:</b>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'deleteMovie', `step` = 'movie-remove' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if($step == "movie-remove" and $text != "🗑️ Kino o'chirish" and in_array((string)$cid,$admin)){
$code = intval($text);
$stmt = mysqli_prepare($connect,"SELECT * FROM `data` WHERE `id` = ?");
mysqli_stmt_bind_param($stmt,"i",$code);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if($row){
$stmt = mysqli_prepare($connect,"DELETE FROM `data` WHERE `id` = ?");
mysqli_stmt_bind_param($stmt,"i",$code);
mysqli_stmt_execute($stmt);

$settingsRow = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `settings` WHERE `id` = '1'"));
$del = intval($settingsRow['kino2']) + 1;
mysqli_query($connect,"UPDATE `settings` SET `kino2` = '$del' WHERE `id` = '1'");

sendMessage($cid, "🗑️ $code <b>raqamli kino olib tashlandi!</b>", $panel);
}else{
sendMessage($cid, "📛 $code <b>mavjud emas!</b>\n\n🔄 Qayta urinib ko'ring:");
}
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = ".esc($connect,$cid));
exit();
}

#=================================================
# Kanal sozlamalari
#=================================================

else if ($text == "💡 Kino kanal" and in_array((string)$cid,$admin)){
sendMessage($cid, "<b>💡 Kino kanal havolasini yuboring!\n\nNa'muna: @ULoyihalar</b>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'movie_chan', `step` = 'movie_chan' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if ($step == "movie_chan" and $text != "💡 Kino kanal" and in_array((string)$cid,$admin)) {
$nn = bot('getchat',['chat_id'=>$text]);
$nn_id = $nn->result->id ?? null;
if($nn_id){
sendMessage($cid, "<b>✅ $text (".str_replace('-100','',$nn_id).") ga o'zgartirildi.</b>", $panel);
file_put_contents("admin/kino.txt", $nn_id);
}else{
sendMessage($cid, "<b>⚠️ Kanal topilmadi. Bot kanalda admin ekanligiga ishonch hosil qiling.</b>", $panel);
}
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if ($text == "📈 Reklama" and in_array((string)$cid,$admin)){
sendMessage($cid, "<b>📈 Reklamani yuboring!\n\nNa'muna:</b> <pre>@%kino% kanali uchun maxsus joylandi!</pre>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'ads_set', `step` = 'ads_set' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if ($step == "ads_set" and $text != "📈 Reklama" and in_array((string)$cid,$admin)) {
sendMessage($cid, "<b>✅ Reklama matni o'zgartirildi.</b>", $panel);
file_put_contents("admin/rek.txt", $text);
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if ($text == "💬 Kanallar" and in_array((string)$cid,$admin)){
sendMessage($cid, "<b>🔰 Kanallar bo'limi:\n🆔 Admin: $cid</b>", $kanallar_p);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'channels' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if ($text == "🔷 Kanal ulash" and in_array((string)$cid,$admin)){
sendMessage($cid, "<b>Majbur obuna ulamoqchi bo'lgan kanaldan (forward) shaklida habar olib yuboring.</b>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'channelsAdd', `step` = 'channel-add' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if ($step == "channel-add" and $text != "🔷 Kanal ulash" and in_array((string)$cid,$admin)){
$channel_id = $message->forward_from_chat->id ?? null;
if($channel_id){
$channel_name = bot('getChat',['chat_id'=>$channel_id])->result->title ?? $channel_id;
if(getAdmin($channel_id) != true){
sendMessage($cid, "<b>⚠️ Bot ushbu kanalda admin emas</b>", $cancel);
}else{
sendMessage($cid, "<b>✅ $channel_name - qabul qilindi, endi havola kiriting!</b>", $cancel);
$kanal = trim(file_get_contents("admin/kanal.txt"));
file_put_contents("admin/kanal.txt", $kanal == '' ? $channel_id : "$kanal\n$channel_id");
file_put_contents("admin/channel.id",$channel_id);
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'url' WHERE `id` = ".esc($connect,$cid));
}
}else{
sendMessage($cid, "<b>Majbur obuna ulamoqchi bo'lgan kanaldan (forward) shaklida habar olib yuboring.</b>", $cancel);
}
exit();
}

else if($step == "url" and $text){
$channel_id = file_get_contents("admin/channel.id");
file_put_contents("admin/links/$channel_id",$text);
@unlink("admin/channel.id");
sendMessage($cid, "<b>✅ Qabul qilindi!</b>", $panel);
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if ($text == "🔶 Kanal uzish" and in_array((string)$cid,$admin)){
sendMessage($cid, "<b>✅ Kanallar uzildi.</b>", $kanallar_p);
deleteFolder("admin/links");
deleteFolder("admin/zayavka");
mkdir("admin/links"); mkdir("admin/zayavka");
file_put_contents("admin/kanal.txt","");
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'deleteChan' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if ($text == "🟩 Majburish a'zolik" and in_array((string)$cid,$admin)){
sendMessage($cid, "<b>🟩 Majburish a'zolik kanallari:</b>\n\n". file_get_contents("admin/kanal.txt"), $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'channels' WHERE `id` = ".esc($connect,$cid));
exit();
}

#=================================================
# Bloklash
#=================================================

else if ($text == "🔴 Blocklash" and in_array((string)$cid,$admin)){
sendMessage($cid, "<b>Foydalanuvchi ID raqamini kiriting:</b>\n\n<i>M-n: $cid</i>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'addblock', `step` = 'blocklash' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if ($step == "blocklash" and $text != "🔴 Blocklash" and in_array((string)$cid,$admin)){
$target = intval($text);
sendMessage($cid, "<b>✅ $target blocklandi!</b>", $panel);
$stmt = mysqli_prepare($connect,"UPDATE `user_id` SET `ban` = 1 WHERE `id` = ?");
mysqli_stmt_bind_param($stmt,"s",$target);
mysqli_stmt_execute($stmt);
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if ($text == "🟢 Blockdan olish" and in_array((string)$cid,$admin)){
sendMessage($cid, "<b>Foydalanuvchi ID raqamini kiriting:</b>\n\n<i>M-n: $cid</i>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'deleteBlock', `step` = 'blockdanolish' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if ($step == "blockdanolish" and $text != "🟢 Blockdan olish" and in_array((string)$cid,$admin)){
$target = intval($text);
sendMessage($cid, "<b>✅ $target blockdan olindi!</b>", $panel);
$stmt = mysqli_prepare($connect,"UPDATE `user_id` SET `ban` = 0 WHERE `id` = ?");
mysqli_stmt_bind_param($stmt,"s",$target);
mysqli_stmt_execute($stmt);
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = ".esc($connect,$cid));
exit();
}

#=================================================
# Ommaviy xabar (Post / Forward)
#=================================================

else if($text == "✍️ Post xabar" and in_array((string)$cid,$admin)){
sendMessage($cid, "<b>Xabaringizni yuboring:</b>",$cancel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'post_msg', `step` = 'post_send' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if ($step == "post_send" and $text != "✍️ Post xabar" and in_array((string)$cid,$admin)){
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = ".esc($connect,$cid));
$msgSent = sendMessage($cid, "✅ <b>Xabar yuborish boshlandi!</b>", $panel);
$msg = $msgSent->result->message_id;
$yuborildi = 0; $yuborilmadi = 0;
$result = mysqli_query($connect, "SELECT * FROM `user_id`");
while($row = mysqli_fetch_assoc($result)){
$uid = $row['id'];
$resp = copyMessage($uid, $cid, $mid);
$ok = $resp->ok ?? false;
if ($ok) $yuborildi++;
else {
$yuborilmadi++;
$stmt = mysqli_prepare($connect,"UPDATE user_id SET sana = 'tark' WHERE id = ?");
mysqli_stmt_bind_param($stmt,"s",$uid);
mysqli_stmt_execute($stmt);
}
editMessageText($cid, $msg, "✅ <b>Yuborildi:</b> {$yuborildi}taga\n❌ <b>Yuborilmadi:</b> {$yuborilmadi}taga");
}
deleteMessage($cid, $msg);
sendMessage($cid, "💡 <b>Xabar yuborish tugatildi.</b>\n\n✅ <b>Yuborildi:</b> {$yuborildi}taga\n❌ <b>Yuborilmadi:</b> {$yuborilmadi}taga\n\n<b>⏰ Soat: $soat | 📆 Sana: $sana</b>", $panel);
exit();
}

else if($text == "📬 Forward xabar" and in_array((string)$cid,$admin)){
sendMessage($cid, "<b>Xabaringizni yuboring:</b>",$cancel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'post_msg', `step` = 'forward_send' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if ($step == "forward_send" and $text != "📬 Forward xabar" and in_array((string)$cid,$admin)){
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = ".esc($connect,$cid));
$msgSent = sendMessage($cid, "✅ <b>Xabar yuborish boshlandi!</b>", $panel);
$msg = $msgSent->result->message_id;
$result = mysqli_query($connect, "SELECT * FROM `user_id`");
$yuborildi = 0; $yuborilmadi = 0;
while($row = mysqli_fetch_assoc($result)){
$uid = $row['id'];
$resp = forwardMessage($uid, $cid, $mid);
$ok = $resp->ok ?? false;
if ($ok) $yuborildi++;
else {
$yuborilmadi++;
$stmt = mysqli_prepare($connect,"UPDATE user_id SET sana = 'tark' WHERE id = ?");
mysqli_stmt_bind_param($stmt,"s",$uid);
mysqli_stmt_execute($stmt);
}
editMessageText($cid, $msg, "✅ <b>Yuborildi:</b> {$yuborildi}taga\n❌ <b>Yuborilmadi:</b> {$yuborilmadi}taga");
}
deleteMessage($cid, $msg);
sendMessage($cid, "💡 <b>Xabar yuborish tugatildi.</b>\n\n✅ <b>Yuborildi:</b> {$yuborildi}taga\n❌ <b>Yuborilmadi:</b> {$yuborilmadi}taga\n\n<b>⏰ Soat: $soat | 📆 Sana: $sana</b>", $panel);
exit();
}

#=================================================
# Statistika
#=================================================

else if($text == "📊 Statistika" and in_array((string)$cid,$admin)){
$res = mysqli_query($connect, "SELECT * FROM `user_id`");
$us = mysqli_num_rows($res);
$resp = mysqli_query($connect, "SELECT * FROM `user_id` WHERE `sana` = 'tark'");
$tark = mysqli_num_rows($resp);
$active = $us - $tark;
$res = mysqli_query($connect, "SELECT * FROM `data`");
$kin = mysqli_num_rows($res);
$ping = sys_getloadavg()[2] ?? 0;
$roow = mysqli_fetch_assoc(mysqli_query($connect,"SELECT * FROM `settings` WHERE `id` = '1'"));
$code = $roow['kino'];
$deleted = $roow['kino2'];
sendMessage($cid, "
💡 <b>O'rtacha yuklanish:</b> <code>$ping</code>

• <b>Jami a'zolar:</b> $us ta
• <b>Tark etgan a'zolar:</b> $tark ta
• <b>Faol a'zolar:</b> $active ta
—————————————
• <b>Faol kinolar:</b> $kin ta
• <b>O'chirilgan kinolar:</b> $deleted ta
• <b>Barcha kinolar:</b> $code ta", $panel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'stat' WHERE `id` = ".esc($connect,$cid));
exit();
}

#=================================================
# Adminlar bo'limi
#=================================================

else if(($text == "👨‍💼 Adminlar" or $data == "admins") and in_array((string)$cid,$admin)){
if(isset($data)) deleteMessage($cid, $mid);
$keyBot = json_encode(['inline_keyboard'=>[
[['text'=>"➕ Yangi admin qo'shish",'callback_data'=>"add-admin"]],
[['text'=>"📑 Ro'yxat",'callback_data'=>"list-admin"],['text'=>"🗑 O'chirish",'callback_data'=>"remove"]],
]]);
sendMessage($cid, "👇🏻 <b>Quyidagilardan birini tanlang:</b>", $keyBot);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'admins' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if($data == "list-admin"){
$adminsList = file_get_contents("admin/admins.txt");
$keyBot = json_encode(['inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"admins"]],
]]);
editMessageText($cid, $mid, "<b>👮 Adminlar ro'yxati:</b>\n\n$adminsList", $keyBot);
exit();
}

else if($data == "add-admin" and (string)$cid == (string)$umidjon){
deleteMessage($cid, $mid);
sendMessage($cid, "<b>Kerakli iD raqamni kiriting:</b>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'add-admin' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if($step == "add-admin" and (string)$cid == (string)$umidjon){
if(is_numeric($text)){
if((string)$text != (string)$umidjon){
file_put_contents("admin/admins.txt", "\n$text", FILE_APPEND);
sendMessage($umidjon, "✅ <b>$text endi bot admini.</b>", $panel);
}else{
sendMessage($cid, "<b>Kerakli iD raqamni kiriting:</b>");
}
}else{
sendMessage($cid, "<b>Kerakli iD raqamni kiriting:</b>");
}
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if($data == "remove" and (string)$cid == (string)$umidjon){
deleteMessage($cid, $mid);
sendMessage($cid, "<b>Kerakli iD raqamni kiriting:</b>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'remove-admin' WHERE `id` = ".esc($connect,$cid));
exit();
}

else if($step == "remove-admin" and (string)$cid == (string)$umidjon){
if(is_numeric($text)){
if((string)$text != (string)$umidjon){
$files = file_get_contents("admin/admins.txt");
$file = str_replace("$text", '', $files);
file_put_contents("admin/admins.txt",$file);
sendMessage($umidjon, "✅ <b>$text endi botda admin emas.</b>", $panel);
}else{
sendMessage($cid, "<b>Kerakli iD raqamni kiriting:</b>");
}
}else{
sendMessage($cid, "<b>Kerakli iD raqamni kiriting:</b>");
}
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = ".esc($connect,$cid));
exit();
}

#=================================================
# Kino kodi orqali qidirish (oddiy foydalanuvchi)
#=================================================

else if(isset($text) and $text != "/start" and $step == '0'){

if(mb_stripos($text,"/start ")!==false){
$text = trim(explode(" ",$text)[1]);
}
if($text == "/rand"){
$son = mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `data`"));
if($son > 0){
$randRow = mysqli_fetch_assoc(mysqli_query($connect,"SELECT * FROM `data` ORDER BY RAND() LIMIT 1"));
$text = $randRow['id'];
}
}

if(joinchat($cid)==true){
if(is_numeric($text)){
$keyBot = json_encode(['inline_keyboard'=>[
[['text'=>"↗️ Do'stlarga ulashish",'url'=>"https://t.me/share/url/?url=https://t.me/$bot?start=$text"]],
[['text'=>"🔎 Boshqa kodlar",'url'=>"https://t.me/$kino"]],
]]);
$code = intval($text);
$stmt = mysqli_prepare($connect, "SELECT * FROM `data` WHERE `id` = ?");
mysqli_stmt_bind_param($stmt,"i",$code);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if(!$row){
sendMessage($cid, "📛 $text <b>kodli kino mavjud emas!</b>");
}else{
$fname = base64_decode($row['film_name']);
$f_id = $row['file_id'];
sendVideo($cid, $f_id, "<b>$fname</b>\n\n$reklama",$keyBot);
}
}else{
sendMessage($cid, "<b>📛 Faqat raqamlardan foydalaning!</b>");
}
}
exit();
}

?>
