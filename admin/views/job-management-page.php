<?php
// /admin/views/job-management-page.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

global $jobs, $total_jobs, $current_page, $per_page, $search_term, $status_filter;

?>
<div class="wrap oo-job-management-page">
    <h1><?php esc_html_e( 'Job Management', 'operations-organizer' ); ?></h1>

    <?php if ( isset( $GLOBALS['oo_job_error'] ) ) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html( $GLOBALS['oo_job_error'] ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( isset( $GLOBALS['oo_job_success'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html( $GLOBALS['oo_job_success'] ); ?></p>
        </div>
    <?php endif; ?>

    <div class="oo-add-job-form-container">
        <h2><?php esc_html_e( 'Add New Job', 'operations-organizer' ); ?></h2>
        <form method="post" class="oo-add-job-form">
            <?php wp_nonce_field( 'oo_add_job_nonce', 'oo_add_job_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="job_number"><?php esc_html_e( 'Job Number', 'operations-organizer' ); ?> <span class="required">*</span></label></th>
                    <td><input type="text" id="job_number" name="job_number" required /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="client_name"><?php esc_html_e( 'Client Name', 'operations-organizer' ); ?></label></th>
                    <td><input type="text" id="client_name" name="client_name" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="client_contact"><?php esc_html_e( 'Client Contact', 'operations-organizer' ); ?></label></th>
                    <td><textarea id="client_contact" name="client_contact" rows="2"></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="start_date"><?php esc_html_e( 'Start Date', 'operations-organizer' ); ?></label></th>
                    <td><input type="date" id="start_date" name="start_date" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="due_date"><?php esc_html_e( 'Due Date', 'operations-organizer' ); ?></label></th>
                    <td><input type="date" id="due_date" name="due_date" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="overall_status"><?php esc_html_e( 'Status', 'operations-organizer' ); ?></label></th>
                    <td>
                        <select id="overall_status" name="overall_status">
                            <option value="Pending"><?php esc_html_e( 'Pending', 'operations-organizer' ); ?></option>
                            <option value="In Progress"><?php esc_html_e( 'In Progress', 'operations-organizer' ); ?></option>
                            <option value="Completed"><?php esc_html_e( 'Completed', 'operations-organizer' ); ?></option>
                            <option value="Cancelled"><?php esc_html_e( 'Cancelled', 'operations-organizer' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="notes"><?php esc_html_e( 'Notes', 'operations-organizer' ); ?></label></th>
                    <td><textarea id="notes" name="notes" rows="3"></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Job Streams', 'operations-organizer' ); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php esc_html_e( 'Select streams for this job', 'operations-organizer' ); ?></legend>
                            <?php foreach ($GLOBALS['streams'] as $stream): ?>
                            <label for="stream_<?php echo esc_attr($stream->stream_id); ?>">
                                <input type="checkbox" id="stream_<?php echo esc_attr($stream->stream_id); ?>" name="stream_<?php echo esc_attr($stream->stream_id); ?>" value="1" />
                                <?php echo esc_html($stream->stream_name); ?>
                            </label><br />
                            <?php endforeach; ?>
                        </fieldset>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit_add_job" id="submit_add_job" class="button button-primary" value="<?php esc_attr_e( 'Add Job', 'operations-organizer' ); ?>" />
            </p>
        </form>
    </div>

    <h2><?php esc_html_e( 'Jobs List', 'operations-organizer' ); ?></h2>

    <form method="get" class="oo-filters-form">
        <input type="hidden" name="page" value="oo_jobs" />
        <div class="wp-filter">
            <div class="filter-items">
                <label for="status_filter" class="screen-reader-text"><?php esc_html_e('Filter by status', 'operations-organizer');?></label>
                <select name="status_filter" id="status_filter">
                    <option value=""><?php esc_html_e('All Statuses', 'operations-organizer');?></option>
                    <option value="Pending" <?php selected($status_filter, 'Pending'); ?>><?php esc_html_e('Pending', 'operations-organizer');?></option>
                    <option value="In Progress" <?php selected($status_filter, 'In Progress'); ?>><?php esc_html_e('In Progress', 'operations-organizer');?></option>
                    <option value="Completed" <?php selected($status_filter, 'Completed'); ?>><?php esc_html_e('Completed', 'operations-organizer');?></option>
                    <option value="Cancelled" <?php selected($status_filter, 'Cancelled'); ?>><?php esc_html_e('Cancelled', 'operations-organizer');?></option>
                </select>
                <input type="submit" name="filter_action" class="button" value="<?php esc_attr_e('Filter', 'operations-organizer');?>">
            </div>
            <p class="search-box">
                <label class="screen-reader-text" for="job-search-input"><?php esc_html_e( 'Search Jobs:', 'operations-organizer' ); ?></label>
                <input type="search" id="job-search-input" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search jobs...', 'operations-organizer' ); ?>" />
                <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Jobs', 'operations-organizer' ); ?>" />
            </p>
        </div>
    </form>
    <div class="clear"></div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e( 'Job Number', 'operations-organizer' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Client', 'operations-organizer' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Start Date', 'operations-organizer' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Due Date', 'operations-organizer' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Status', 'operations-organizer' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Streams', 'operations-organizer' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Actions', 'operations-organizer' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! empty( $jobs ) ) : ?>
                <?php foreach ( $jobs as $job ) : 
                    // Get associated streams for this job
                    $job_stream_links = OO_DB::get_job_streams_for_job($job->job_id);
                    $stream_names = array();
                    
                    foreach ($job_stream_links as $job_stream) {
                        // Use the helper function to get stream name
                        $stream_name = oo_get_stream_name($job_stream->stream_id);
                        if ($stream_name) {
                            $stream_names[] = esc_html($stream_name);
                        }
                    }
                ?>
                    <tr>
                        <td><?php echo esc_html( $job->job_number ); ?></td>
                        <td><?php echo esc_html( $job->client_name ); ?></td>
                        <td><?php echo $job->start_date ? esc_html( $job->start_date ) : '—'; ?></td>
                        <td><?php echo $job->due_date ? esc_html( $job->due_date ) : '—'; ?></td>
                        <td><?php echo esc_html( $job->overall_status ); ?></td>
                        <td><?php echo !empty($stream_names) ? implode(', ', $stream_names) : '—'; ?></td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=oo_dashboard&filter_job_number=' . urlencode( $job->job_number ) ) ); ?>" class="button button-secondary"><?php esc_html_e( 'View Logs', 'operations-organizer' ); ?></a>
                            <button class="button button-secondary oo-edit-job-button" data-job-id="<?php echo esc_attr( $job->job_id ); ?>"><?php esc_html_e( 'Edit', 'operations-organizer' ); ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7"><?php esc_html_e( 'No jobs found.', 'operations-organizer' ); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php
    // Pagination
    if ( $total_jobs > $per_page ) {
        $base_url = remove_query_arg( array( 'paged', 'filter_action' ), wp_unslash( $_SERVER['REQUEST_URI'] ) );
        $page_links = paginate_links( array(
            'base' => $base_url . '%_%',
            'format' => '&paged=%#%',
            'prev_text' => __( '&laquo; Previous' ),
            'next_text' => __( 'Next &raquo;' ),
            'total' => ceil( $total_jobs / $per_page ),
            'current' => $current_page,
            'add_args' => array_map( 'urlencode', array_filter( compact( 's', 'status_filter' ) ) )
        ) );

        if ( $page_links ) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
        }
    }
    ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Edit job button functionality
    $('.oo-edit-job-button').on('click', function() {
        var jobId = $(this).data('job-id');
        alert('Job editing will open a detailed form with stream-specific fields for Soft Content, Electronics, Art, and Content data. This functionality will be implemented in the next update. Job ID: ' + jobId);
    });
});
</script> 