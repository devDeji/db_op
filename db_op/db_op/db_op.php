<?php
/*
Plugin Name: Database operations 
Plugin URI: https://antechncom.wordpress.com/
Description: A database manager for various MY_SQL database operations 
Version: 1.0
Author: Bowale Joseph
Author URI: https://antechncom.wordpress.com/about-us/
License: GPLv2
*/
/*  Copyright 2019  Bowale Joseph  (email : devjoe2016@gmail.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
// Call function when plugin is activated
register_activation_hook( __FILE__, 'db_op_install' );

// function to pull available database from db
function db_op_retrieve_dbs(){
	global $wpdb;
	$mytables=$wpdb->get_results("SHOW TABLES");
	return $mytables;
}

function db_op_install() {
	$db_op_retrieve_dbs = db_op_retrieve_dbs();
	
    //setup default option values
    $db_op_options_arr = array(
        'db_op_first_tb' => $db_op_retrieve_dbs,
		'db_op_second_tb' => $db_op_retrieve_dbs,
		'db_op_sel_first_tb' => "",
		'db_op_sel_second_tb' => "",
		'db_op_sel_query_op' => "",
		'db_op_query_op' => array( 'UNION' )
    );
    //save our default option values
    update_option( 'db_op_options', $db_op_options_arr );
	
}

add_action( 'admin_menu', 'db_op_reg_custom_menu_page' );
function db_op_reg_custom_menu_page() {
  // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
  add_menu_page( 'Db Op', 'Db Op', 'manage_options', 'db_op.php', 'db_op_menu_page', 'dashicons-vault', 90 );
}

//Called upon saving the configuration on the settings page. Manupulates submitted post contents
function db_op_parse_posted_content(){
		global $wpdb;
		$mytables;
		$db_op_tb1 = sanitize_text_field($_POST['tb1']); 
		$db_op_tb2 = sanitize_text_field($_POST['tb2']); 
		
		//If submitted post content is empty
	if ( ! empty( $_POST ) && current_user_can('edit_posts') ) {
		//check nonce for security
		check_admin_referer( 'db_op_menu-save', 'db_op-plugin' );
		
			if($db_op_tb1 == "" && $db_op_tb2 == ""){
				?><div><blockquote><?php echo 'Please select at least one table'; ?> </div></blockquote>
			<?php }
			
			else if($db_op_tb1 == "" && $db_op_tb2 != ""){	
				?><div><table  class="widefat fixed" > <h3> Displaying query information of table <?php echo  esc_attr($db_op_tb2) ?></h3><thead class="thead-light"><tr class="table-info">
				<?php 
				$db_op_sql =  "
				SELECT DISTINCT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME ='{$db_op_tb2}'";
				$db_op_cols = $wpdb->get_col($db_op_sql);
				foreach ($db_op_cols as $t)
				{
					   ?><th scope="col"><?php echo esc_attr($t) ?> </th><?php
				}
				?></tr></thead><tbody><tr><?php 
				$db_op_sql =  "
				SELECT * FROM $db_op_tb2";
				$mytables = $wpdb->get_results($db_op_sql);
				foreach ( $mytables as $mytable ) 
				{
					?><tr><?php
					foreach ($mytable as $t)
					{
						?><td class="column-columnname"> <?php echo  esc_attr($t); ?></td><?php
					}?></tr><?php
				}
				?></h2> </tbody></table><?php
				
			}else if ($db_op_tb2 == "" && $db_op_tb1 != ""){
				?><div><table  class="widefat fixed" > <h3> Displaying query information of table <?php  echo esc_attr($db_op_tb1) ?></h3><thead class="thead-light"><tr class="table-info">
				<?php 
				$sql =  "
				SELECT DISTINCT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME ='{$db_op_tb1}'";
				$db_op_cols = $wpdb->get_col($sql);
				foreach ($db_op_cols as $t)
				{
					   ?><th scope="col"><?php echo esc_attr($t) ?> </th><?php
				}
				?></tr></thead><tbody><tr><?php 
				$sql =  "
				SELECT * FROM $db_op_tb1";
				$mytables = $wpdb->get_results($sql);
				foreach ( $mytables as $mytable ) 
				{
					?><tr><?php
					foreach ($mytable as $t)
					{	
						?><td class="column-columnname"> <?php echo  esc_attr($t); ?></td><?php
					}?></tr><?php
				}
				?></h2> </tbody></table><?php
			} else {
				$qry = sanitize_text_field($_POST['qryop']);
				$repl = str_replace('wp_', '', $db_op_tb1);
				$repl1 = str_replace('wp_', '', $db_op_tb2);
				$query = "
					SELECT * 
					FROM {$wpdb->$repl} {$qry}
					SELECT * 
					FROM {$wpdb->$repl1}";

				$mytables = $wpdb->get_results($query);
				?> <div><table id="greentable" class="widefat fixed"> <thead>
				<h1>
				<?php echo esc_attr($qry). " of " . esc_attr($db_op_tb1). " and " . esc_attr($db_op_tb2). '<br>';
				?><tbody>
				</h1></thead>
				<h2><?php 
				if (empty($mytables)) {
					echo '<h2>To use this UNION clause, each SELECTED TABLE must have</br>
								
								- The same number of columns selected</br>
								- The same number of column expressions</br>
								- The same data type and</br>
								- Have them in the same order</br>
								But they need not have to be in the same length.</h2>';
				}
				else {
					foreach ( $mytables as $mytable ) 
					{
						echo '<tr>';
						foreach ($mytable as $t)
						{
							echo '<td class="column-columnname">'. esc_attr($t). '</td>';
						}
						echo '</tr>';
					}
				}
				?></h2> </tbody></table><?php
			}
		}
		//setup default option values
		$db_op_options_arr = array(
			'db_op_sel_first_tb' => $db_op_tb1,
			'db_op_sel_second_tb' => $db_op_tb2,
			'db_op_sel_query_op' => $qry,
			'mytables' => $mytables,
			'cols' => $db_op_cols,
			'db_op_query_op' => array( 'UNION')
		);
		//save our default option values
		update_option( 'db_op_options', $db_op_options_arr );
		?><div>  
		<h2>Comfortable with the results, please use with shortcode '[do]' on any page or as a widget display.</h2></br>
		<?php 
		return $mytables;
}
function db_op_menu_page(){
	//set the option array values to variables
	$db_op_first_tb = db_op_retrieve_dbs();
	$db_op_second_tb = db_op_retrieve_dbs();
	$db_op_query_op = array( 'UNION');
	?>
    <div class="wrap">
    <h2><?php _e( 'Database operations options', 'db_op_plugin' ) ?></h2>
	<h3> Select at least one table </h3>
    <form method="post" action="<?php echo get_permalink(); ?>">
	
        <?php settings_fields( 'db_op-settings-group' ); 
		
		//nonce field for security
		wp_nonce_field( 'db_op_menu-save', 'db_op-plugin' );
		
		?>
        <table class="form-table">
           <tr valign="top">
            <th scope="row"><?php _e( 'Current Database', 'db_op_plugin' ) ?></th>
			<td><h4><?php echo esc_attr( $site_title ); ?> </h4></td>
			</tr>
			<tr valign="top">
            <th scope="row"><?php _e( 'Select first database table', 'db_op_plugin' ) ?></th>
			<td>
			<select name="tb1" >
			<option value="" > None </option>
			<?php
			 foreach ($db_op_first_tb as $mytable)
				{
					foreach ($mytable as $t) 
					{   ?>
						  <option value="<?php echo esc_attr( $t); ?>" > <?php echo esc_attr( $t ); ?> </option>
			<?php  }
				}
			?>
			</select>
			</td>
			<th scope="row"><?php _e( 'Select second database table', 'db_op_plugin' ) ?></th>
			<td>
			<select name="tb2" >
			<option value="" > None </option>
			<?php
			 foreach ($db_op_second_tb as $mytable)
				{
					foreach ($mytable as $t) 
					{   ?>
						  <option value="<?php echo  esc_attr( $t); ?>" > <?php echo esc_attr( $t ); ?> </option>
			<?php  }
				}
			?>
			</select>
			</td>
			</tr>
			<tr valign="top">
            <th scope="row"><?php _e( 'Select database query option', 'db_op_plugin' ) ?></th>
			<td>
			<select name="qryop" ><?php
			 foreach ($db_op_query_op as $mytable)
				{
					?>
						  <option value="<?php echo  $mytable; ?>" > <?php echo esc_attr( $mytable ); ?> </option>
				<?php	
				}
			?>
			</select>
			</td>
			</tr>
			 
            <tr valign="bottom">
			<td  colspan="2" style="align:right;">
			
				
			</td>
		</tr>
        </table>

        <p class="submit">
        <input type="submit" class="button-primary" value="<?php _e( 'Perform set query', 'db_op_plugin' ); ?>" />
        </p>

    </form>
	 
    </div>
<?php
db_op_parse_posted_content();
}

add_action( 'admin_init', 'db_op_register_settings' );
function db_op_register_settings() {
    //register the array of settings
    register_setting( 'db_op-settings-group', 'db_op_options', 'db_op_sanitize_options' );
}
function db_op_sanitize_options( $options ) {
	$options['db_op_sel_first_tb'] = ( ! empty( $options['db_op_sel_first_tb'] ) ) ? sanitize_text_field( $options['db_op_sel_first_tb'] ) : '';
	$options['db_op_sel_second_tb'] = ( ! empty( $options['db_op_sel_second_tb'] ) ) ? sanitize_text_field( $options['db_op_sel_second_tb'] ) : '';
	$options['db_op_sel_query_op'] = ( ! empty( $options['db_op_sel_query_op'] ) ) ? sanitize_text_field( $options['db_op_sel_query_op'] ) : '';
	$options['db_op_first_tb'] = ( ! empty( $options['db_op_first_tb'] ) ) ? sanitize_text_field( $options['db_op_first_tb'] ) : '';
	$options['db_op_second_tb'] = ( ! empty( $options['db_op_second_tb'] ) ) ? sanitize_text_field( $options['db_op_second_tb'] ) : '';
	$options['db_op_query_op'] = ( ! empty( $options['db_op_query_op'] ) ) ? sanitize_text_field( $options['db_op_query_op'] ) : '';
	return $options;
}
//perforn query operation
function perform_query(  ) {
    global $wpdp;
	
    //load options array
    $db_op_options_arr = get_option( 'db_op_options' );
	$mytables = $db_op_options_arr['mytables'];
	
    return $mytables;
}

// Action hook to create the products shortcode
add_shortcode( 'do', 'db_op_shortcode' );
//create shortcode
function db_op_shortcode( $atts, $content = null ) {
    global $wpdp;
	//load options array
    $db_op_options_arr = get_option( 'db_op_options' );
	$cols = $db_op_options_arr['cols'];
	$mytables = perform_query();
	$res = '<div><table  class="table table-hover" > <h3> Displaying query information of table(s) ' . $db_op_options_arr['db_op_sel_first_tb'] . ' / ' . $db_op_options_arr['db_op_sel_second_tb'].'</h3><thead class="thead-light"><tr class="table-info">';
	if($cols != ""){
	foreach ($cols as $t)
		{
			$res .= '<th scope="col">'.$t.'</th>';
		}
	}
	$res .= '</tr></thead>';
	foreach ( $mytables as $mytable ) 
		{
			$res .= '<tbody> <tr class="alternate">';
			foreach ($mytable as $t)
			{
				$res .= '<td class="column-columnname"> '.$t. '</td> ';
			}
			//$res .= '</br>';
			$res .= '</tbody> </tr>';
		}
		$res .= '</table></div>';
	
		return $res;
}
// Action hook to create plugin widget
add_action( 'widgets_init', 'db_op_register_widgets' );
//register the widget
function db_op_register_widgets() {
	
    register_widget( 'db_op_widget' );
	
}
//db_op_widget class
class db_op_widget extends WP_Widget {
    //process our new widget
    function db_op_widget() {
		
        $widget_ops = array(
			'classname'   => 'db_op-widget-class',
			'description' => __( 'Display Query results','db_op_plugin' ) );
        $this->WP_Widget( 'db_op_widget', __( 'Database Operations Widget','db_op_plugin'), $widget_ops );
		
    }
    //build our widget settings form
    function form( $instance ) {
		
        $defaults = array( 
			'title'           => __( 'Query results', 'db_op_plugin' ));
		
        $instance = wp_parse_args( (array) $instance, $defaults );
        $title = $instance['title'];
        ?>
            <p><?php _e('Title', 'db_op_plugin') ?>: 
				<input class="widefat" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>
            
        <?php
    }
    //save our widget settings
    function update( $new_instance, $old_instance ) {
		
        $instance = $old_instance;
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        return $instance;
		
    }
     //display our widget
    function widget( $args, $instance ) {
        global $post;
		
        extract( $args );
        echo $before_widget;
        $title = apply_filters( 'widget_title', $instance['title'] );
        if ( ! empty( $title ) ) { echo $before_title . esc_html( $title ) . $after_title; };
            $db_op_options_arr = get_option( 'db_op_options' );
            ?>
			<p>
				<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title_attribute(); ?> Query Information">
				<?php the_title(); ?>
				</a>
			</p>
			<?php
			
			 $mytables = perform_query();
			 $cols = $db_op_options_arr['cols'];
			$mytables = perform_query();
			$res = '<div><table  class="table table-hover" > <h3> Displaying query information of table(s) ' . $db_op_options_arr['db_op_sel_first_tb'] . ' / ' . $db_op_options_arr['db_op_sel_second_tb'].'</h3><thead class="thead-light"><tr class="table-info">';
			if($cols != ""){
			foreach ($cols as $t)
				{
					$res .= '<th scope="col">'.$t.'</th>';
				}
			}
			$res .= '</tr></thead>';
			foreach ( $mytables as $mytable ) 
				{
					$res .= '<tbody> <tr class="alternate">';
					foreach ($mytable as $t)
					{
						$res .= '<td class="column-columnname"> '.$t. '</td> ';
					}
					//$res .= '</br>';
					$res .= '</tbody> </tr>';
				}
		$res .= '</table></div>';
				
			echo $res;
            echo '<hr>';
			echo $after_widget;
		
    }
	
}