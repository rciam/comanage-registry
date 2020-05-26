<script type="text/javascript">
    $(function() {
        //document.getElementById('redirect-spinner').remove();
        // Debug mode enabled: Focus to the current active circle
        var $focusElement = document.getElementById('<?php print $vv_current_step; ?>');
        if ($focusElement) {
            $focusElement.focus();
        }
    });
</script>


<?php
  print $this->Html->css('rciam-pg-vert') . "\n    ";
  print $this->Html->css('rciam-pg-pie') . "\n    ";

$currentIndex = $vv_steps[$vv_current_step];
$maxIndex = end($vv_steps);
$progress_percent = round(($currentIndex/$maxIndex)*100);

  if (empty($vv_on_progress))  {
    $vv_on_progress =  _txt('fd.ef.progress.default');
  }

  if(Configure::read('debug') > 0): ?>
    <div class="vprogress">
      <?php foreach ($vv_steps as $index => $step): ?>
        <?php if ($index === $currentIndex): ?>
          <div tabindex="-1" id="<?php print $step ?>" class="circle active">
            <span class="label"><?php print ($index+1); ?></span>
            <span class="title"><?php print $step; ?></span>
            <div class="spinner-pos" id="redirect-spinner"></div>
          </div>
          <div class="bar"></div>
        <?php elseif ($currentIndex > $index ): ?>
          <div id="<?php print $step ?>" class="circle done">
            <span class="label">✓</span>
            <span class="title"><?php print $step; ?></span>
          </div>
          <?php if (($currentIndex-1) === $index): ?>
            <div class="bar active"></div>
          <?php else: ?>
            <div class="bar done"></div>
          <?php endif; ?>
        <?php else: ?>
          <div id="<?php print $step; ?>" class="circle">
            <span class="label"><?php print ($index+1); ?></span>
            <span class="title"><?php print $step; ?></span>
          </div>
          <div class="bar"></div>
        <?php endif; ?>
      <?php endforeach;?>
    </div>
  <?php else: ?>
    <div class="comments"><?php print $vv_on_progress; ?></div>
    <div id="circular-progress" class="circular-progress">
      <div class="progress"><?php print $progress_percent; ?>%</div>
      <script type="application/javascript">
          let $circularProgress = document.getElementById("circular-progress");
          maxPercent = 100;
          remainingColor = '#E0E0E0';
          progressColor = '#03A9F4';
          percent = <?php print $progress_percent; ?>;
          increment = 360 / maxPercent;
          half = Math.round(maxPercent / 2);
          if (percent < half) {
              nextdeg = 90 + (increment * percent);
              $circularProgress.style.backgroundImage =
                  "linear-gradient(90deg, " + remainingColor +" 50%, transparent 50%, transparent)," +
                  "linear-gradient(" + nextdeg.toString() + "deg, " + progressColor + " 50%, " + remainingColor + " 50%, " +remainingColor +")";
          } else {
              nextdeg = -90 + (increment * (percent - half));
              $circularProgress.style.backgroundImage =
                  "linear-gradient(" + nextdeg.toString() + "deg, " + progressColor + " 50%, transparent 50%, transparent)," +
                  "linear-gradient(270deg, " + progressColor + " 50%, " + remainingColor + " 50%, " + remainingColor +")";
          }
      </script>
    </div>
  <?php endif; ?>
