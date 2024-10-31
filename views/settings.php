<?php
/**
 * View for settings.
 */
if ( !defined( "ABSPATH" ) ) {
    exit;
}

$delete_option = get_option( 'qas_data_delete', 'no' );
?>

<div class="container">     
    <h4><?php _e( 'Settings', 'quiz-and-survey' ); ?></h4>
    <div id="qas-message-container">
        <div id="qas-message" class="alert alert-dismissible" style="display:none"></div>
    </div>
    <div class="panel panel-primary">
        <div class="panel-heading"><?php _e( 'Data Option', 'quiz-and-survey' ); ?></div>
        <div class="panel-body">
            <form id="qas-settings-data-delete-form" action="" method="post" class="form" style="border: 0px;margin: 0px">
                <div class="checkbox">
                    <label>
                      <input type="checkbox" id="qas-settings-delete-data" <?php if( $delete_option == 'yes' ) { echo 'checked'; }?>><?php _e( 'Delete the stored data when uninstalling the plugin.', 'quiz-and-survey' ); ?>
                    </label>
                </div>
                <input type="submit" id="submit-settings-data-delete" value="<?php esc_attr_e( 'Save', 'quiz-and-survey' ); ?>" class="btn btn-primary" />
            </form>    
        </div>
    </div>
</div>