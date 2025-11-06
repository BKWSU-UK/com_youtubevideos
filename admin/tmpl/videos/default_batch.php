<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_youtubevideos
 *
 * @copyright   Copyright (C) 2024 Brahma Kumaris. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \BKWSU\Component\Youtubevideos\Administrator\View\Videos\HtmlView $this */

$published = (int) $this->state->get('filter.published');
?>

<div class="modal fade" id="collapseModal" tabindex="-1" role="dialog" aria-labelledby="collapseModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="collapseModalLabel"><?php echo Text::_('COM_YOUTUBEVIDEOS_BATCH_OPTIONS'); ?></h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
            </div>
            <div class="modal-body">
                <p><?php echo Text::_('COM_YOUTUBEVIDEOS_BATCH_TIP'); ?></p>
                <div class="container-fluid">
                    <?php echo $this->batchForm->renderFieldset('batch'); ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?php echo Text::_('JCANCEL'); ?>
                </button>
                <button type="submit" class="btn btn-success" onclick="Joomla.submitbutton('videos.batch');">
                    <?php echo Text::_('JGLOBAL_BATCH_PROCESS'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

