<?php
// /admin/views/stream-management-page.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

global $streams, $total_streams, $current_page, $per_page, $search_term, $active_filter;

?>
<div class="wrap oo-stream-management-page">
    <h1><?php esc_html_e( 'Stream Management', 'operations-organizer' ); ?></h1>

    <button id="openAddOOStreamModalBtn" class="page-title-action oo-open-modal-button" data-modal-id="addOOStreamModal"><?php esc_html_e( 'Add New Stream', 'operations-organizer' ); ?></button>

    <form method="get" class="oo-filters-form">
        <input type="hidden" name="page" value="oo_streams" />
        <div class="wp-filter">
            <div class="filter-items">
                <label for="status_filter" class="screen-reader-text"><?php esc_html_e('Filter by status', 'operations-organizer');?></label>
                <select name="status_filter" id="status_filter">
                    <option value="all" <?php selected($active_filter, 'all'); ?>><?php esc_html_e('All Statuses', 'operations-organizer');?></option>
                    <option value="active" <?php selected($active_filter, 'active'); ?>><?php esc_html_e('Active', 'operations-organizer');?></option>
                    <option value="inactive" <?php selected($active_filter, 'inactive'); ?>><?php esc_html_e('Inactive', 'operations-organizer');?></option>
                </select>
                <input type="submit" name="filter_action" class="button" value="<?php esc_attr_e('Filter', 'operations-organizer');?>">
            </div>
            <p class="search-box">
                <label class="screen-reader-text" for="stream-search-input"><?php esc_html_e( 'Search Streams:', 'operations-organizer' ); ?></label>
                <input type="search" id="stream-search-input" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search by Name/Description', 'operations-organizer' ); ?>" />
                <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Streams', 'operations-organizer' ); ?>" />
            </p>
        </div>
    </form>
    <div class="clear"></div>

    <div id="oo-stream-list-table-wrapper">
        <table class="wp-list-table widefat fixed striped table-view-list streams">
            <thead>
                <tr>
                    <?php 
                    $columns = [
                        'stream_name' => __('Stream Name', 'operations-organizer'),
                        'stream_description' => __('Description', 'operations-organizer'),
                        'phase_count' => __('Phases', 'operations-organizer'),
                        'status' => __('Status', 'operations-organizer'),
                        'actions' => __('Actions', 'operations-organizer'),
                    ];
                    $current_orderby = isset($GLOBALS['orderby']) ? $GLOBALS['orderby'] : 'stream_name';
                    $current_order = isset($GLOBALS['order']) ? strtolower($GLOBALS['order']) : 'asc';

                    foreach($columns as $slug => $title) {
                        $class = "manage-column column-$slug";
                        $sort_link = '';
                        if ($slug === 'stream_name') { // Sortable column
                            $order = ($current_orderby == $slug && $current_order == 'asc') ? 'desc' : 'asc';
                            $class .= $current_orderby == $slug ? " sorted $current_order" : " sortable $order";
                            $sort_link_url = add_query_arg(['orderby' => $slug, 'order' => $order]);
                            $sort_link = "<a href=\"".esc_url($sort_link_url)."\"><span>$title</span><span class=\"sorting-indicator\"></span></a>";
                        } else {
                            $sort_link = $title;
                        }
                        echo "<th scope=\"col\" id=\"$slug\" class=\"$class\">$sort_link</th>";
                    }
                    ?>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php if ( ! empty( $streams ) ) : ?>
                    <?php foreach ( $streams as $stream ) : 
                        // Get phase count for this stream
                        $phase_count = 0;
                        if (method_exists('OO_Stream', 'get_by_id') && method_exists('OO_Stream', 'get_phases')) {
                            $stream_obj = OO_Stream::get_by_id($stream->stream_id);
                            if ($stream_obj) {
                                $phase_count = count($stream_obj->get_phases());
                            }
                        }
                    ?>
                        <tr id="stream-<?php echo $stream->stream_id; ?>" class="<?php echo $stream->is_active ? 'active' : 'inactive'; ?>">
                            <td class="stream_name column-stream_name" data-colname="<?php esc_attr_e('Stream Name', 'operations-organizer'); ?>">
                                <?php echo esc_html( $stream->stream_name ); ?>
                            </td>
                            <td class="stream_description column-stream_description" data-colname="<?php esc_attr_e('Description', 'operations-organizer'); ?>">
                                <?php echo esc_html( $stream->stream_description ); ?>
                            </td>
                            <td class="phase_count column-phase_count" data-colname="<?php esc_attr_e('Phases', 'operations-organizer'); ?>">
                                <?php echo intval($phase_count); ?>
                            </td>
                            <td class="status column-status" data-colname="<?php esc_attr_e('Status', 'operations-organizer'); ?>">
                                <?php if ( $stream->is_active ) : ?>
                                    <span style="color: green;"><?php esc_html_e( 'Active', 'operations-organizer' ); ?></span>
                                <?php else : ?>
                                    <span style="color: red;"><?php esc_html_e( 'Inactive', 'operations-organizer' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="actions column-actions" data-colname="<?php esc_attr_e('Actions', 'operations-organizer'); ?>">
                                <button class="button-secondary oo-edit-stream-button" data-stream-id="<?php echo esc_attr( $stream->stream_id ); ?>"><?php esc_html_e('Edit', 'operations-organizer'); ?></button>
                                <?php if ( $stream->is_active ) : ?>
                                    <button class="button-secondary oo-toggle-status-stream-button oo-deactivate" data-stream-id="<?php echo esc_attr( $stream->stream_id ); ?>" data-new-status="0"><?php esc_html_e('Deactivate', 'operations-organizer'); ?></button>
                                <?php else : ?>
                                    <button class="button-secondary oo-toggle-status-stream-button oo-activate" data-stream-id="<?php echo esc_attr( $stream->stream_id ); ?>" data-new-status="1"><?php esc_html_e('Activate', 'operations-organizer'); ?></button>
                                <?php endif; ?>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=oo_phases&stream_filter=' . $stream->stream_id)); ?>" class="button-secondary"><?php esc_html_e('Manage Phases', 'operations-organizer'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="<?php echo count($columns); ?>"><?php esc_html_e( 'No streams found.', 'operations-organizer' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <?php 
                    foreach($columns as $slug => $title) {
                        echo "<th scope=\"col\" class=\"manage-column column-$slug\">$title</th>";
                    }
                    ?>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Pagination -->
    <?php
    if ( $total_streams > $per_page ) {
        $base_url = remove_query_arg(array('paged', 'filter_action'), wp_unslash($_SERVER['REQUEST_URI']));
        $page_links = paginate_links( array(
            'base' => $base_url . '%_%',
            'format' => '&paged=%#%', 
            'prev_text' => __( '&laquo; Previous' ),
            'next_text' => __( 'Next &raquo;' ),
            'total' => ceil( $total_streams / $per_page ),
            'current' => $current_page,
            'add_args' => array_map('urlencode', array_filter(compact('s', 'status_filter', 'orderby', 'order')))
        ) );

        if ( $page_links ) {
            echo "<div class=\"tablenav\"><div class=\"tablenav-pages\">$page_links</div></div>";
        }
    }
    ?>

    <!-- Add Stream Modal -->
    <div id="addOOStreamModal" class="oo-modal" style="display:none;">
        <div class="oo-modal-content">
            <span class="oo-close-button">&times;</span>
            <h2><?php esc_html_e( 'Add New Stream', 'operations-organizer' ); ?></h2>
            <form id="oo-add-stream-form">
                <?php wp_nonce_field( 'oo_add_stream_nonce', 'oo_add_stream_nonce' ); ?>
                <table class="form-table oo-form-table">
                    <tr valign="top">
                        <th scope="row"><label for="add_stream_name"><?php esc_html_e( 'Stream Name', 'operations-organizer' ); ?></label></th>
                        <td><input type="text" id="add_stream_name" name="stream_name" required /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="add_stream_description"><?php esc_html_e( 'Description', 'operations-organizer' ); ?></label></th>
                        <td><textarea id="add_stream_description" name="stream_description"></textarea></td>
                    </tr>
                </table>
                <?php submit_button( __( 'Add Stream', 'operations-organizer' ), 'primary', 'submit_add_stream' ); ?>
            </form>
        </div>
    </div>

    <!-- Edit Stream Modal -->
    <div id="editOOStreamModal" class="oo-modal" style="display:none;">
        <div class="oo-modal-content">
            <span class="oo-close-button">&times;</span>
            <h2><?php esc_html_e( 'Edit Stream', 'operations-organizer' ); ?></h2>
            <form id="oo-edit-stream-form">
                <?php wp_nonce_field( 'oo_edit_stream_nonce', 'oo_edit_stream_nonce' ); ?>
                <input type="hidden" id="edit_stream_id" name="edit_stream_id" value="" />
                <table class="form-table oo-form-table">
                    <tr valign="top">
                        <th scope="row"><label for="edit_modal_stream_name"><?php esc_html_e( 'Stream Name', 'operations-organizer' ); ?></label></th>
                        <td><input type="text" id="edit_modal_stream_name" name="edit_stream_name" required /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="edit_modal_stream_description"><?php esc_html_e( 'Description', 'operations-organizer' ); ?></label></th>
                        <td><textarea id="edit_modal_stream_description" name="edit_stream_description"></textarea></td>
                    </tr>
                </table>
                <?php submit_button( __( 'Save Changes', 'operations-organizer' ), 'primary', 'submit_edit_stream' ); ?>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Modal open/close logic is in admin-scripts.js

    window.loadStreamsTable = function() {
        window.location.reload(); 
    };

    // Form submission for Add Stream
    $('#oo-add-stream-form').on('submit', function(event) {
        event.preventDefault();
        var formData = $(this).serialize() + '&action=oo_add_stream';
        
        $.post(oo_data.ajax_url, formData, function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                $('#addOOStreamModal').hide();
                $('#oo-add-stream-form')[0].reset();
                loadStreamsTable();
            } else {
                showNotice('error', response.data.message || 'Could not add stream.');
            }
        }).fail(function() {
            showNotice('error', 'Request to add stream failed.');
        });
    });

    // Handle "Edit Stream" button click
    $('.oo-edit-stream-button').on('click', function() {
        var streamId = $(this).data('stream-id');
        $.post(oo_data.ajax_url, {
            action: 'oo_get_stream',
            stream_id: streamId,
            _ajax_nonce_get_stream: oo_data.nonce_edit_stream
        }, function(response) {
            if (response.success) {
                $('#edit_stream_id').val(response.data.stream_id);
                $('#edit_modal_stream_name').val(response.data.stream_name);
                $('#edit_modal_stream_description').val(response.data.stream_description);
                $('#editOOStreamModal').show();
            } else {
                 showNotice('error', response.data.message || 'Could not load stream data.');
            }
        }).fail(function() {
            showNotice('error', 'Request to load stream data failed.');
        });
    });

    // Form submission for Edit Stream
    $('#oo-edit-stream-form').on('submit', function(event) {
        event.preventDefault();
        var formData = $(this).serialize() + '&action=oo_update_stream';
        
        $.post(oo_data.ajax_url, formData, function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                $('#editOOStreamModal').hide();
                loadStreamsTable();
            } else {
                showNotice('error', response.data.message || 'Could not update stream.');
            }
        }).fail(function() {
            showNotice('error', 'Request to update stream failed.');
        });
    });

    // Handle "Toggle Status" button click
    $('.oo-toggle-status-stream-button').on('click', function() {
        var streamId = $(this).data('stream-id');
        var newStatus = $(this).data('new-status');
        var confirmMessage = newStatus == 1 ? 
            '<?php echo esc_js(__("Are you sure you want to activate this stream?", "operations-organizer")); ?>' : 
            '<?php echo esc_js(__("Are you sure you want to deactivate this stream?", "operations-organizer")); ?>';

        if (!confirm(confirmMessage)) {
            return;
        }
        
        $.post(oo_data.ajax_url, {
            action: 'oo_toggle_stream_status',
            stream_id: streamId,
            is_active: newStatus,
            _ajax_nonce: oo_data.nonce_toggle_status
        }, function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                loadStreamsTable(); 
            } else {
                showNotice('error', response.data.message || 'Could not change stream status.');
            }
        }).fail(function() {
            showNotice('error', 'Request to change stream status failed.');
        });
    });
    
    // Common function to display notices
    if (typeof showNotice !== 'function') {
        window.showNotice = function(type, message) {
            $('.oo-notice').remove();
            var noticeHtml = '<div class="notice notice-' + type + ' is-dismissible oo-notice"><p>' + message + '</p>' +
                             '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
            $('.oo-stream-management-page h1').first().after(noticeHtml);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $('.oo-notice').fadeOut('slow', function() { $(this).remove(); });
            }, 5000);
            
            // Allow manual dismiss
            $('.oo-notice .notice-dismiss').on('click', function(event) {
                event.preventDefault();
                $(this).closest('.oo-notice').remove();
            });
        };
    }
});
</script> 