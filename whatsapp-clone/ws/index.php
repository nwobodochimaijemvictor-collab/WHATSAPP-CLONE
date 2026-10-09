<?php
require_once __DIR__.'/config.php';
$me=require_login();
$chats=$chat->getChats((int)$me['id']);
$selectedId=(int)($_GET['user']??0);
$selected=$selectedId?$chat->getUser($selectedId):null;
if(!$selected && $chats){$selected=$chat->getUser((int)$chats[0]['id']);$selectedId=(int)$selected['id'];}
$conversation=$selected?$chat->getOrCreateConversation((int)$me['id'],(int)$selected['id']):0;
if($conversation)$chat->markRead($conversation,(int)$me['id']);
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf" content="<?=e(csrf_token())?>"><title>WhatsApp Clone</title><link rel="stylesheet" href="style.css"></head><body>
<div class="app-shell">
<aside class="sidebar" id="sidebar">
<header class="side-header"><a href="profile.php" class="me-profile"><div class="avatar avatar-me"><?php if($me['avatar']):?><img src="<?=e($me['avatar'])?>"><?php else:?><?=e(mb_strtoupper(mb_substr($me['name'],0,1)))?><?php endif;?></div><div class="me-name"><?=e($me['name'])?></div></a><div class="side-actions"><a href="profile.php" title="Settings">⚙</a><form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><button title="Log out">↪</button></form></div></header>
<div class="search-wrap"><span>⌕</span><input id="search" placeholder="Search or start new chat" autocomplete="off"></div>
<div class="section-title">Chats</div><div id="chat-list" class="chat-list">
<?php foreach($chats as $c):?><a class="chat-item <?=((int)$c['id']===$selectedId?'active':'')?>" data-name="<?=e(strtolower($c['name'].' '.$c['phone']))?>" href="?user=<?=(int)$c['id']?>"><div class="avatar"><span><?=e(mb_strtoupper(mb_substr($c['name'],0,1)))?></span></div><div class="chat-item-main"><div class="chat-item-top"><strong><?=e($c['name'])?></strong><time><?= $c['last_message_at'] ? e(date('g:i A',strtotime($c['last_message_at']))) : ''?></time></div><div class="chat-preview"><span><?=e($c['last_message']??$c['about'])?></span><?php if((int)$c['unread_count']>0):?><b><?=e((string)$c['unread_count'])?></b><?php endif;?></div></div></a><?php endforeach; ?>
</div>
<div id="search-results" class="chat-list hidden"></div>
</aside>
<main class="chat-pane <?= $selected?'has-chat':'' ?>">
<?php if($selected):?><header class="chat-header"><button class="mobile-back" id="mobile-back">‹</button><div class="avatar"><span><?=e(mb_strtoupper(mb_substr($selected['name'],0,1)))?></span></div><div class="header-user"><strong><?=e($selected['name'])?></strong><span id="presence"><?= $chat->isOnline($selected['last_seen'])?'online':'last seen '.e(date('g:i A',strtotime($selected['last_seen']??'now')))?></span></div><div class="header-actions"><button title="Search">⌕</button><button title="More">⋮</button></div></header>
<section class="messages-area" id="messages"><div class="date-pill">Today</div><div class="encryption-note">🔒 Messages are protected in this chat.</div><div id="message-list"></div></section>
<form class="composer" id="composer"><button type="button" class="icon-btn" id="emoji">☺</button><input type="text" id="message-input" maxlength="5000" placeholder="Type a message" autocomplete="off"><button type="button" class="icon-btn" id="attach">＋</button><button class="send-btn" title="Send">➤</button></form>
<?php else:?><section class="welcome"><div class="welcome-icon">☏</div><h1>WhatsApp Clone</h1><p>Send and receive messages from other users of this app.</p><small>End-to-end encryption is represented visually here; this demo does not provide WhatsApp's actual E2EE protocol.</small></section><?php endif;?></main>
</div>
<?php if($selected):?><script>window.APP={me:<?=json_encode((int)$me['id'])?>,conversation:<?=json_encode($conversation)?>,other:<?=json_encode((int)$selected['id'])?>,csrf:<?=json_encode(csrf_token())?>};</script><script src="app.js"></script><?php else:?><script src="search.js"></script><?php endif;?><script src="common.js"></script></body></html>
