<?php
/**
 * Document filter form template
 *
 * @package CorporateDocuments
 */

defined('ABSPATH') || exit;
?>

<form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" 
      method="POST" 
      id="cdox-filter-form" 
      class="cdox-filter-form">
    
    <fieldset>
        <legend><?php esc_html_e('Filter Options', 'cdox'); ?></legend>

        <?php if ($show_type_filter): ?>
            <div class="cdox-filter-section">
                <label for="cdox-filter-doctypes" class="cdox-filter-label">
                    <?php esc_html_e('Document Type:', 'cdox'); ?>
                </label>
                <div class="cdox-select-wrapper">
                    <select name="cdoxfilterdoctypes" id="cdox-filter-doctypes">
                        <option value="cdox-all-doctypes">
                            <?php esc_html_e('All Document Types', 'cdox'); ?>
                        </option>
                        <?php foreach ($this->get_available_document_types($document_types) as $type): ?>
                            <option value="<?php echo esc_attr($type->slug); ?>">
                                <?php echo esc_html($type->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($show_year_filter): ?>
            <div class="cdox-filter-section">
                <label for="cdox-filter-year" class="cdox-filter-label">
                    <?php esc_html_e('Year:', 'cdox'); ?>
                </label>
                <div class="cdox-select-wrapper">
                    <select name="cdoxfilteryear" id="cdox-filter-year">
                        <option value="cdox-all-years" <?php selected(!$initial_current_year); ?>>
                            <?php esc_html_e('All Years', 'cdox'); ?>
                        </option>
                        <?php 
                        $years = $this->document_repository->get_document_years();
                        $current_year = date('Y');
                        foreach ($years as $year => $count): 
                        ?>
                            <option value="<?php echo esc_attr($year); ?>"
                                    <?php selected($initial_current_year && $year === $current_year); ?>>
                                <?php echo esc_html(sprintf(
                                    '%s (%d)',
                                    $year,
                                    $count
                                )); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($show_order_filter): ?>
            <div class="cdox-filter-section">
                <span class="cdox-filter-label">
                    <?php esc_html_e('Sort Order:', 'cdox'); ?>
                </span>
                <div class="cdox-radio-group">
                    <label class="cdox-radio-label">
                        <input type="radio" 
                               name="dateorder" 
                               value="DESC" 
                               checked="checked">
                        <?php esc_html_e('Newest First', 'cdox'); ?>
                    </label>
                    <label class="cdox-radio-label">
                        <input type="radio" 
                               name="dateorder" 
                               value="ASC">
                        <?php esc_html_e('Oldest First', 'cdox'); ?>
                    </label>
                </div>
            </div>
        <?php endif; ?>
    </fieldset>

    <div class="cdox-filter-actions">
        <button type="submit" class="cdox-filter-submit">
            <?php esc_html_e('Apply Filters', 'cdox'); ?>
        </button>
    </div>

    <?php wp_nonce_field('cdox_filter_nonce', 'cdox_filter_nonce'); ?>
    <input type="hidden" name="action" value="cdox_filter">
    <input type="hidden" name="show_date_column" value="<?php echo $show_date_column ? '1' : '0'; ?>">
</form>