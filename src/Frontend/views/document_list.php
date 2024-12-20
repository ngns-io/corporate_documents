<?php
/**
 * Document list view template
 *
 * @package CorporateDocuments
 */

defined('ABSPATH') || exit;
?>

<div id="cdox-document-list" class="cdox-document-list <?php echo $show_date_column ? 'show-dates' : ''; ?>">
    <?php if (empty($documents)): ?>
        <p class="cdox-no-documents"><?php esc_html_e('No documents found.', 'cdox'); ?></p>
    <?php else: ?>
        <?php foreach ($documents as $document): ?>
            <div class="cdox-document-item">
                <?php if ($show_date_column): ?>
                    <div class="cdox-document-date">
                        <?php echo esc_html($document->get_formatted_date()); ?>
                    </div>
                <?php endif; ?>
                
                <div class="cdox-document-title">
                    <i class="fa <?php echo esc_attr($document->get_icon_class()); ?>"></i>
                    <a href="<?php echo esc_url($document->get_download_url()); ?>"
                       data-document-id="<?php echo esc_attr($document->get_id()); ?>"
                       class="cdox-document-link"
                       target="_blank"
                       rel="noopener">
                        <?php echo esc_html($document->get_title()); ?>
                    </a>
                </div>

                <?php if ($document->has_meta_data()): ?>
                    <div class="cdox-document-meta">
                        <?php if ($document->get_file_size()): ?>
                            <span class="cdox-document-size">
                                <?php echo esc_html($document->get_formatted_file_size()); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($document->get_download_count()): ?>
                            <span class="cdox-document-downloads">
                                <?php
                                printf(
                                    esc_html(_n(
                                        '%s download',
                                        '%s downloads',
                                        $document->get_download_count(),
                                        'cdox'
                                    )),
                                    number_format_i18n($document->get_download_count())
                                );
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="cdox-loading">
    <span class="cdox-loading-text"><?php esc_html_e('Loading...', 'cdox'); ?></span>
</div>