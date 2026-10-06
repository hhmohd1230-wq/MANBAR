<?php
$title = 'Messages';
$lastMessageId = $conv ? (int) end($conv)['id'] : 0;
?>
<div class="messages-page">
  <div class="messages-heading">
    <div><span class="eyebrow">Campus conversations</span><h1>Messages</h1><p>Share ideas, files and quick updates with your MANBAR community.</p></div>
    <span class="messages-private"><?= icon('lock', 15) ?> Private conversations</span>
  </div>

  <div class="dm-shell <?= $other ? 'has-conversation' : '' ?>">
    <aside class="dm-sidebar" aria-label="Conversations">
      <div class="dm-sidebar-head"><div><b>Chats</b><span><?= count($threads) ?> conversation<?= count($threads) === 1 ? '' : 's' ?></span></div><a class="dm-new" href="<?= e(url('people')) ?>" aria-label="Start a new conversation"><?= icon('plus', 18) ?></a></div>
      <div class="dm-thread-list">
        <?php foreach ($threads as $oid => $t): $pp = $people[$oid]; $last = $t['last']; ?>
          <a class="thread <?= $other && (int) $other['id'] === (int) $oid ? 'on' : '' ?>" href="<?= e(url('messages/' . $oid)) ?>">
            <?= avatar($pp, 46) ?>
            <span class="grow thread-copy"><span class="thread-name"><b><?= e($pp['full_name']) ?></b><?php if ($last): ?><time><?= e(date('H:i', strtotime($last['created_at']))) ?></time><?php endif ?></span><span class="thread-preview"><?php if ($last && trim((string) $last['body']) !== ''): ?><?= e(excerpt($last['body'], 40)) ?><?php elseif ($last && str_starts_with((string) ($last['attachment_type'] ?? ''), 'audio/')): ?>Voice message<?php elseif ($last && !empty($last['attachment_name'])): ?>Attachment · <?= e(excerpt($last['attachment_name'], 25)) ?><?php else: ?>New conversation<?php endif ?></span></span>
            <?php if ($t['unread']): ?><span class="unread"><?= $t['unread'] ?></span><?php endif ?>
          </a>
        <?php endforeach ?>
        <?php if (!$threads): ?><div class="dm-empty-threads"><?= icon('chat', 26) ?><h3>No conversations yet</h3><p>Find a classmate, mentor or teacher and start talking.</p><a class="btn btn-primary btn-sm" href="<?= e(url('people')) ?>">Find people</a></div><?php endif ?>
      </div>
    </aside>

    <section class="dm-conversation">
      <?php if ($other): ?>
        <header class="dm-conversation-head">
          <a class="dm-mobile-back" href="<?= e(url('messages')) ?>" aria-label="Back to conversations"><?= icon('chevron-right', 20) ?></a>
          <?= avatar($other, 46) ?>
          <div class="grow"><a href="<?= e(url('profile/' . $other['id'])) ?>"><b><?= e($other['full_name']) ?></b></a><?= verified_badge($other) ?><span class="dm-presence"><i></i> MANBAR member · <?= e(role_label($other['role'])) ?></span></div>
          <a class="dm-profile-link" href="<?= e(url('profile/' . $other['id'])) ?>">View profile</a>
        </header>

        <div class="chat dm" id="dmChat" data-peer="<?= (int) $other['id'] ?>" data-last="<?= $lastMessageId ?>">
          <div class="dm-day"><span>Conversation</span></div>
          <div id="dmEmpty" class="dm-chat-empty" <?= $conv ? 'hidden' : '' ?>><?= icon('message', 25) ?><p>Say hello to <?= e(explode(' ', $other['full_name'])[0]) ?> 👋</p><span>Messages and shared files stay together here.</span></div>
          <?php foreach ($conv as $m): $mine = (int) $m['sender_id'] === (int) $u['id']; ?>
            <div class="msg <?= $mine ? 'mine' : '' ?>" data-message-id="<?= (int) $m['id'] ?>"><div class="bub"><?= message_attachment_html($m) ?><?php if (trim((string) $m['body']) !== ''): ?><div class="dm-message-text"><?= rich($m['body']) ?></div><?php endif ?><div class="dm-message-meta"><time><?= e(date('H:i', strtotime($m['created_at']))) ?></time><?php if ($mine): ?><?= icon('check', 13) ?><?php endif ?></div></div></div>
          <?php endforeach ?>
          <div class="dm-typing" id="dmTyping" hidden><span></span><span></span><span></span><em><?= e(explode(' ', $other['full_name'])[0]) ?> is typing</em></div>
        </div>

        <div class="dm-composer-wrap">
          <div class="dm-attachment-preview" id="dmAttachmentPreview" hidden><span class="dm-preview-icon"><?= icon('file', 20) ?></span><span class="grow"><b id="dmAttachmentName"></b><small id="dmAttachmentSize"></small></span><button type="button" class="dm-preview-remove" id="dmAttachmentRemove" aria-label="Remove attachment"><?= icon('x', 16) ?></button></div>
          <div class="dm-voice-recorder" id="dmVoiceRecorder" hidden aria-live="polite">
            <div class="dm-recording-live" id="dmRecordingLive" hidden>
              <span class="dm-recording-dot" aria-hidden="true"></span>
              <span class="dm-recording-copy"><b>Recording voice message</b><small>Tap stop when you are finished · 2 minutes maximum</small></span>
              <time class="dm-recording-time" id="dmRecordingTime" datetime="PT0S">00:00</time>
              <button class="dm-record-cancel" id="dmRecordCancel" type="button">Cancel</button>
              <button class="dm-record-stop" id="dmRecordStop" type="button"><span aria-hidden="true"></span> Stop</button>
            </div>
            <div class="dm-voice-ready" id="dmVoiceReady" hidden>
              <span class="dm-audio-icon"><?= icon('mic', 20) ?></span>
              <audio id="dmVoicePreview" controls preload="metadata"></audio>
              <span class="dm-voice-ready-copy"><b>Voice message ready</b><small id="dmVoiceSize">Review it before sending</small></span>
              <button class="dm-preview-remove" id="dmVoiceDiscard" type="button" aria-label="Discard voice message"><?= icon('trash', 16) ?></button>
            </div>
          </div>
          <div class="dm-emoji-panel" id="dmEmojiPanel" hidden aria-label="Choose an emoji">
            <?php foreach (['😀','😂','😊','😍','🤩','🥳','👍','👏','🙌','💚','❤️','🔥','✨','💡','🎉','🤝','✅','🙏','👋','🚀','📚','🎓','💻','☕'] as $emoji): ?><button type="button" data-dm-emoji="<?= e($emoji) ?>"><?= e($emoji) ?></button><?php endforeach ?>
          </div>
          <form method="post" enctype="multipart/form-data" action="<?= e(url('messages/' . $other['id'])) ?>" class="dm-composer" id="dmForm" data-max-bytes="<?= (int) (cfg('message_upload_max_mb') ?: 12) * 1024 * 1024 ?>">
            <?= csrf_field() ?>
            <label class="dm-tool" for="dmAttachment" title="Attach a file"><?= icon('paperclip', 21) ?><span class="sr-only">Attach a file</span></label>
            <input class="sr-only" id="dmAttachment" type="file" name="attachment" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.txt,.csv,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.webm,.ogg,.m4a,.wav,.aac">
            <button class="dm-tool" type="button" id="dmEmojiButton" title="Add an emoji" aria-expanded="false"><?= icon('smile', 21) ?><span class="sr-only">Add an emoji</span></button>
            <button class="dm-tool dm-record-button" type="button" id="dmRecordButton" title="Record a voice message" aria-label="Record a voice message" aria-pressed="false"><?= icon('mic', 21) ?></button>
            <button class="dm-tool writing-quick" type="button" data-ai-quick title="Improve writing" aria-label="Improve writing"><?= icon('sparkles', 21) ?></button>
            <textarea class="dm-input" name="body" id="dmInput" data-ai-writing="message" maxlength="2000" rows="1" placeholder="Type a message" autocomplete="off"></textarea>
            <button class="dm-send" type="submit" aria-label="Send message"><?= icon('send', 20) ?></button>
          </form>
          <p class="dm-file-note">Voice messages, images, PDF, Office, text, CSV and ZIP · up to <?= (int) (cfg('message_upload_max_mb') ?: 12) ?> MB</p>
        </div>
      <?php else: ?>
        <div class="dm-select-empty"><span class="dm-select-art"><?= icon('chat', 39) ?></span><h2>Your conversations, together</h2><p>Select a chat on the left or find someone new in the MANBAR community.</p><a class="btn btn-primary" href="<?= e(url('people')) ?>"><?= icon('users', 18) ?> Explore people</a></div>
      <?php endif ?>
    </section>
  </div>
</div>
