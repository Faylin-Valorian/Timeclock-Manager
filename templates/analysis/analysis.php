<?php
use OCP\Util;

Util::addScript('timeclock-manager', 'analysis');
Util::addStyle('timeclock-manager', 'sidebar');
Util::addStyle('timeclock-manager', 'analysis');
?>

<div id="app">
    <?php print_unescaped($this->inc('components/sidebar', ['mode' => 'analysis'])); ?>

    <div id="app-content">
        <div id="app-content-wrapper">
            <div id="analysis-root"></div>
        </div>
    </div>
</div>

