<?php
declare(strict_types=1); require_once __DIR__.'/config.php'; header('Content-Type: application/json; charset=utf-8');
$me=require_login(); $action=$_POST['action']??$_GET['action']??'';
if(in_array($action,['send','read','delete','profile'],true) && !verify_csrf($_POST['csrf']??'')){http_response_code(403);echo json_encode(['ok'=>false,'error'=>'Invalid security token']);exit;}
try{
switch($action){
case 'messages': $cid=(int)($_GET['conversation']??0); $after=(int)($_GET['after']??0); $chat->markRead($cid,(int)$me['id']); echo json_encode(['ok'=>true,'messages'=>$chat->getMessages($cid,(int)$me['id'],100,$after)]);break;
case 'send': $cid=(int)($_POST['conversation']??0); $msg=(string)($_POST['message']??''); $id=$chat->sendMessage($cid,(int)$me['id'],$msg); echo json_encode(['ok'=>(bool)$id,'id'=>$id]);break;
case 'read': $chat->markRead((int)$_POST['conversation'],(int)$me['id']); echo json_encode(['ok'=>true]);break;
case 'delete': echo json_encode(['ok'=>$chat->deleteMessage((int)$_POST['message_id'],(int)$me['id'])]);break;
case 'search': echo json_encode(['ok'=>true,'users'=>$chat->searchUsers((int)$me['id'],(string)($_GET['q']??''))]);break;
case 'chats': echo json_encode(['ok'=>true,'chats'=>$chat->getChats((int)$me['id'])]);break;
default: http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Unknown action']);
}
}catch(Throwable $e){http_response_code(500);echo json_encode(['ok'=>false,'error'=>'Server error']);}
