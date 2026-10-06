<button class="guide-fab" id="guideFab" aria-label="Open MANBAR assistant" aria-expanded="false"><?= icon('sparkles', 20) ?><span>Ask MANBAR AI</span></button>
<section class="guide" id="guide" aria-label="MANBAR assistant">
  <div class="guide-head"><?= icon('sparkles', 26) ?><div class="grow"><h4>MANBAR Assistant</h4><small>Writing, discovery and campus guidance</small></div><span class="guide-status"><i></i> Ready</span><button class="icon-btn" id="guideClose" aria-label="Close assistant"><?= icon('x', 20) ?></button></div>
  <div class="guide-msgs" id="guideMsgs">
    <div class="gm bot guide-welcome"><b>Hi <?= e(explode(' ', current_user()['full_name'])[0]) ?>. What can I help you move forward?</b><span>I can improve your writing, search real MANBAR projects and people, recommend mentors or courses, and open the right page.</span>
      <div class="sugg">
        <button class="chip outline sm" data-sugg="Find open mobile app projects that match me"><?= icon('rocket', 13) ?> Find projects</button>
        <button class="chip outline sm" data-sugg="Find students who know UI design and Flutter"><?= icon('users', 13) ?> Find teammates</button>
        <button class="chip outline sm" data-sugg="Recommend a mentor for career and capstone advice"><?= icon('compass', 13) ?> Find a mentor</button>
        <button class="chip outline sm" data-sugg="Correct the grammar in: i want build a ai app for studnets"><?= icon('edit', 13) ?> Improve writing</button>
      </div>
    </div>
  </div>
  <form class="guide-foot" id="guideForm"><label class="sr" for="guideIn">Ask MANBAR Assistant</label><textarea class="textarea" id="guideIn" rows="1" maxlength="4000" placeholder="Ask MANBAR about a goal or draft…"></textarea><button class="btn btn-primary" type="submit" aria-label="Send message"><?= icon('send', 18) ?></button></form>
</section>
<?php if (!empty($showTour)): ?>
<div class="product-tour" id="productTour" role="dialog" aria-modal="true" aria-labelledby="tourTitle" aria-describedby="tourText">
  <div class="tour-shade" data-tour-shade="top"></div>
  <div class="tour-shade" data-tour-shade="right"></div>
  <div class="tour-shade" data-tour-shade="bottom"></div>
  <div class="tour-shade" data-tour-shade="left"></div>
  <div class="tour-focus" id="tourFocus" aria-hidden="true"></div>
  <div class="tour-card" id="tourCard">
    <div class="tour-kicker"><span id="tourCount">Step 1 of 7</span><span>Getting started</span></div>
    <h2 id="tourTitle">Start with Ask MANBAR AI</h2>
    <p id="tourText">Tell MANBAR what you want to achieve. It can suggest the right tool, improve a draft, and help you decide what to do next.</p>
    <div class="tour-progress" id="tourProgress" aria-hidden="true"></div>
    <div class="tour-actions">
      <button class="btn btn-ghost" id="tourSkip" type="button">Skip tour</button>
      <div class="row">
        <button class="btn btn-ghost" id="tourBack" type="button">Back</button>
        <button class="btn btn-primary" id="tourNext" type="button">Next <?= icon('arrow-right', 15) ?></button>
      </div>
    </div>
  </div>
</div>
<?php endif ?>
