<?php
/*
BeerXML code adapted from:

Plugin Name: BeerXML Shortcode
Plugin URI: http://wordpress.org/extend/plugins/beerxml-shortcode/
Description: Automatically insert/display beer recipes by linking to a BeerXML document.
Author: Derek Springer
Version: 0.3
Author URI: http://12inchpianist.com
License: GPL2 or later
*/

class BeerXML_Shortcode {
	/**
	 * A simple call to init when constructed
	 */
	function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * BeerXML initialization routines
	 */
	function init() {
		add_shortcode( 'beerxml', array( $this, 'beerxml_shortcode' ) );
	}

	/**
	 * Shortcode for BeerXML
	 * [beerxml recipe=http://example.com/wp-content/uploads/2012/08/bowie-brown.xml cache=10800 metric=true download=true style=true]
	 *
	 * @param  array $atts shortcode attributes
	 *                     recipe - URL to BeerXML document
	 *                     cache - number of seconds to cache recipe
	 *                     metric - true  -> use metric values
	 *                              false -> use U.S. values
	 *                     download - true -> include link to BeerXML file
	 *                     style - true -> include style details
	 * @return string HTML to be inserted in shortcode's place
	 */
	function beerxml_shortcode( $atts ) {
		global $post;

		if ( ! is_array( $atts ) ) {
			return '<!-- BeerXML shortcode passed invalid attributes -->';
		}

		if ( ! isset( $atts['recipe'] ) && ! isset( $atts[0] ) ) {
			return '<!-- BeerXML shortcode source not set -->';
		}

		extract( shortcode_atts( array(
			'recipe'   => null,
			'cache'    => get_option( 'beerxml_shortcode_cache', 60*60*12 ), // cache for 12 hours
			'metric'   => 2 == get_option( 'beerxml_shortcode_units', 1 ), // units
			'download' => get_option( 'beerxml_shortcode_download', 1 ), // include download link
			'style'    => get_option( 'beerxml_shortcode_style', 1 ), // include style details
		), $atts ) );

		if ( ! isset( $recipe ) ) {
			$recipe = $atts[0];
		}

		$recipe = esc_url_raw( $recipe );
		$recipe_filename = pathinfo( $recipe, PATHINFO_FILENAME );
		$recipe_id = "beerxml_shortcode_recipe-{$post->ID}_{$recipe_filename}";

		$cache  = intval( esc_attr( $cache ) );
		if ( -1 == $cache ) { // clear cache if set to -1
			delete_transient( $recipe_id );
			$cache = 0;
		}

		$metric = filter_var( esc_attr( $metric ), FILTER_VALIDATE_BOOLEAN );
		$download = filter_var( esc_attr( $download ), FILTER_VALIDATE_BOOLEAN );
		$style = filter_var( esc_attr( $style ), FILTER_VALIDATE_BOOLEAN );

		if ( ! $cache || false === ( $beer_xml = get_transient( $recipe_id ) ) ) {
			$beer_xml = new BeerXML( $recipe );
		} else {
			// result was in cache, just use that
			return $beer_xml;
		}

		if ( ! $beer_xml->recipes ) { // empty recipe
			return '<!-- Error parsing BeerXML document -->';
		}

		/***************
		 * Recipe Details
		 **************/
		if ( $metric ) {
			$beer_xml->recipes[0]->batch_size = round( $beer_xml->recipes[0]->batch_size, 1 );
			$t_vol = __( 'L', 'beerxml-shortcode' );
		} else {
			$beer_xml->recipes[0]->batch_size = round( $beer_xml->recipes[0]->batch_size * 0.264172, 1 );
			$t_vol = __( 'gal', 'beerxml-shortcode' );
		}

		$btime = round( $beer_xml->recipes[0]->boil_time );
		$t_details = __( 'Recipe Details', 'beerxml-shortcode' );
		$t_size    = __( 'Batch Size', 'beerxml-shortcode' );
		$t_boil    = __( 'Boil Time', 'beerxml-shortcode' );
		$t_time    = __( 'min', 'beerxml-shortcode' );
		$t_ibu     = __( 'IBU', 'beerxml-shortcode' );
		$t_srm     = __( 'SRM', 'beerxml-shortcode' );
		$t_og      = __( 'Est. OG', 'beerxml-shortcode' );
		$t_fg      = __( 'Est. FG', 'beerxml-shortcode' );
		$t_abv     = __( 'ABV', 'beerxml-shortcode' );
		$details = <<<DETAILS
		<div class="beerxml-details">
			<h3>$t_details</h3>
			<table class="table table-condensed">
				<thead>
					<tr>
						<th>$t_size</th>
						<th>$t_boil</th>
						<th>$t_ibu</th>
						<th>$t_srm</th>
						<th>$t_og</th>
						<th>$t_fg</th>
						<th>$t_abv</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>{$beer_xml->recipes[0]->batch_size} $t_vol</td>
						<td>$btime $t_time</td>
						<td>{$beer_xml->recipes[0]->ibu}</td>
						<td>{$beer_xml->recipes[0]->est_color}</td>
						<td>{$beer_xml->recipes[0]->est_og}</td>
						<td>{$beer_xml->recipes[0]->est_fg}</td>
						<td>{$beer_xml->recipes[0]->est_abv}</td>
					</tr>
				</tbody>
			</table>
		</div>
DETAILS;

		/***************
		 * Style Details
		 **************/
		$style_details = '';
		$t_name = __( 'Name', 'beerxml-shortcode' );
		if ( $style && $beer_xml->recipes[0]->style ) {
			$t_style = __( 'Style Details', 'beerxml-shortcode' );
			$t_category = __( 'Cat.', 'beerxml-shortcode' );
			$t_og_range = __( 'OG Range', 'beerxml-shortcode' );
			$t_fg_range = __( 'FG Range', 'beerxml-shortcode' );
			$t_ibu_range = __( 'IBU', 'beerxml-shortcode' );
			$t_srm_range = __( 'SRM', 'beerxml-shortcode' );
			$t_carb_range = __( 'Carb', 'beerxml-shortcode' );
			$t_abv_range = __( 'ABV', 'beerxml-shortcode' );
			$style_details = <<<STYLE
			<div class='beerxml-style'>
				<h3>$t_style</h3>
				<table class="table table-condensed">
					<thead>
						<tr>
							<th>$t_name</th>
							<th>$t_category</th>
							<th>$t_og_range</th>
							<th>$t_fg_range</th>
							<th>$t_ibu_range</th>
							<th>$t_srm_range</th>
							<th>$t_carb_range</th>
							<th>$t_abv_range</th>
						</tr>
					</thead>
					<tbody>
						{$this->build_style( $beer_xml->recipes[0]->style )}
					</tbody>
				</table>
			</div>
STYLE;
		}

		/***************
		 * Fermentables Details
		 **************/
		$fermentables = '';
		$total = BeerXML_Fermentable::calculate_total( $beer_xml->recipes[0]->fermentables );
		foreach ( $beer_xml->recipes[0]->fermentables as $fermentable ) {
			$fermentables .= $this->build_fermentable( $fermentable, $total, $metric );
		}

		$t_fermentables = __( 'Fermentables', 'beerxml-shortcode' );
		$t_amount = __( 'Amount', 'beerxml-shortcode' );
		$fermentables = <<<FERMENTABLES
		<div class='beerxml-fermentables'>
			<h3>$t_fermentables</h3>
			<table class="table table-condensed">
				<thead>
					<tr>
						<th>$t_name</th>
						<th>$t_amount</th>
						<th>%</th>
					</tr>
				</thead>
				<tbody>
					$fermentables
				</tbody>
			</table>
		</div>
FERMENTABLES;

		/***************
		 * Hops Details
		 **************/
		$hops = '';
		if ( $beer_xml->recipes[0]->hops ) {
			foreach ( $beer_xml->recipes[0]->hops as $hop ) {
				$hops .= $this->build_hop( $hop, $metric );
			}

			$t_hops  = __( 'Hops', 'beerxml-shortcode' );
			$t_time  = __( 'Time', 'beerxml-shortcode' );
			$t_use   = __( 'Use', 'beerxml-shortcode' );
			$t_form  = __( 'Form', 'beerxml-shortcode' );
			$t_alpha = __( 'Alpha %', 'beerxml-shortcode' );
			$hops = <<<HOPS
			<div class='beerxml-hops'>
				<h3>$t_hops</h3>
				<table class="table table-condensed">
					<thead>
						<tr>
							<th>$t_name</th>
							<th>$t_amount</th>
							<th>$t_time</th>
							<th>$t_use</th>
							<th>$t_form</th>
							<th>$t_alpha</th>
						</tr>
					</thead>
					<tbody>
						$hops
					</tbody>
				</table>
			</div>
HOPS;
		}

		/***************
		 * Miscs
		 **************/
		$miscs = '';
		if ( $beer_xml->recipes[0]->miscs ) {
			foreach ( $beer_xml->recipes[0]->miscs as $misc ) {
				$miscs .= $this->build_misc( $misc );
			}

			$t_miscs = __( 'Miscs', 'beerxml-shortcode' );
			$t_type = __( 'Type', 'beerxml-shortcode' );
			$miscs = <<<MISCS
			<div class='beerxml-miscs'>
				<h3>$t_miscs</h3>
				<table class="table table-condensed">
					<thead>
						<tr>
							<th>$t_name</th>
							<th>$t_amount</th>
							<th>$t_time</th>
							<th>$t_use</th>
							<th>$t_type</th>
						</tr>
					</thead>
					<tbody>
						$miscs
					</tbody>
				</table>
			</div>
MISCS;
		}

		/***************
		 * Yeast Details
		 **************/
		$yeasts = '';
		if ( $beer_xml->recipes[0]->yeasts ) {
			foreach ( $beer_xml->recipes[0]->yeasts as $yeast ) {
				$yeasts .= $this->build_yeast( $yeast, $metric );
			}

			$t_yeast       = __( 'Yeast', 'beerxml-shortcode' );
			$t_lab         = __( 'Lab', 'beerxml-shortcode' );
			$t_attenuation = __( 'Attenuation', 'beerxml-shortcode' );
			$t_temperature = __( 'Temperature', 'beerxml-shortcode' );
			$yeasts = <<<YEASTS
			<div class='beerxml-yeasts'>
				<h3>$t_yeast</h3>
				<table class="table table-condensed">
					<thead>
						<tr>
							<th>$t_name</th>
							<th>$t_lab</th>
							<th>$t_attenuation</th>
							<th>$t_temperature</th>
						</tr>
					</thead>
					<tbody>
						$yeasts
					</tbody>
				</table>
			</div>
YEASTS;
		}

		/***************
		 * Notes
		 **************/
		$notes = '';
		if ( $beer_xml->recipes[0]->notes ) {
			$t_notes = __( 'Notes', 'beerxml-shortcode' );
			$formatted_notes = preg_replace( '/\n/', '<br />', $beer_xml->recipes[0]->notes );
			$notes = <<<NOTES
			<div class='beerxml-notes'>
				<h3>$t_notes</h3>
				<table class="table table-condensed">
					<tbody>
						<tr>
							<td>$formatted_notes</td>
						</tr>
					</tbody>
				</table>
			</div>
NOTES;
		}

		/***************
		 * Download link
		 **************/
		$link = '';
		if ( $download ) {
			$t_download = __( 'Download', 'beerxml-shortcode' );
			$t_link = __( 'Download this recipe\'s BeerXML file', 'beerxml-shortcode' );
			$link = <<<LINK
			<div class="beerxml-download">
				<h3>$t_download</h3>
				<table class="table table-condensed">
					<tbody>
						<tr>
							<td><a href="$recipe" download="$recipe_filename">$t_link</a></td>
						</tr>
					</tbody>
				</table>
			</div>
LINK;
		}

		// stick 'em all together
		$html = <<<HTML
		<div class='beerxml-recipe'>
			$details
			$style_details
			$fermentables
			$hops
			$miscs
			$yeasts
			$notes
			$link
		</div>
HTML;

		if ( $cache && $beer_xml->recipes ) {
			set_transient( $recipe_id, $html, $cache );
		}

		return $html;
	}

	/**
	 * Build style row
	 * @param  BeerXML_Style 		$style fermentable to display
	 */
	static function build_style( $style ) {
		$category = $style->category_number . ' ' . $style->style_letter;
		$og_range = round( $style->og_min, 3 ) . ' - ' . round( $style->og_max, 3 );
		$fg_range = round( $style->fg_min, 3 ) . ' - ' . round( $style->fg_max, 3 );
		$ibu_range = round( $style->ibu_min, 1 ) . ' - ' . round( $style->ibu_max, 1 );
		$srm_range = round( $style->color_min, 1 ) . ' - ' . round( $style->color_max, 1 );
		$carb_range = round( $style->carb_min, 1 ) . ' - ' . round( $style->carb_max, 1 );
		$abv_range = round( $style->abv_min, 1 ) . ' - ' . round( $style->abv_max, 1 );
		return <<<STYLE
		<tr>
			<td>{$style->name}</td>
			<td>$category</td>
			<td>$og_range</td>
			<td>$fg_range</td>
			<td>$ibu_range</td>
			<td>$srm_range</td>
			<td>$carb_range</td>
			<td>$abv_range %</td>
		</tr>
STYLE;
	}

	/**
	 * Build fermentable row
	 * @param  BeerXML_Fermentable  $fermentable fermentable to display
	 * @param  boolean $metric      true to display values in metric
	 * @return string               table row containing fermentable details
	 */
	static function build_fermentable( $fermentable, $total, $metric = false ) {
		$percentage = round( $fermentable->percentage( $total ), 2 );
		if ( $metric ) {
			$fermentable->amount = round( $fermentable->amount, 3 );
			$t_weight = __( 'kg', 'beerxml-shortcode' );
		} else {
			$fermentable->amount = round( $fermentable->amount * 2.20462, 3 );
			$t_weight = __( 'lbs', 'beerxml-shortcode' );
		}

		return <<<FERMENTABLE
		<tr>
			<td>$fermentable->name</td>
			<td>$fermentable->amount $t_weight</td>
			<td>$percentage</td>
		</tr>
FERMENTABLE;
	}

	/**
	 * Build hop row
	 * @param  BeerXML_Hop          $hop hop to display
	 * @param  boolean $metric      true to display values in metric
	 * @return string               table row containing hop details
	 */
	static function build_hop( $hop, $metric = false ) {
		if ( $metric ) {
			$hop->amount = round( $hop->amount * 1000, 1 );
			$t_weight = __( 'g', 'beerxml-shortcode' );
		} else {
			$hop->amount = round( $hop->amount * 35.274, 2 );
			$t_weight = __( 'oz', 'beerxml-shortcode' );
		}

		if ( $hop->time >= 1440 ) {
			$hop->time = round( $hop->time / 1440, 1);
			$t_time = _n( 'day', 'days', $hop->time, 'beerxml-shortcode' );
		} else {
			$hop->time = round( $hop->time );
			$t_time = __( 'min', 'beerxml-shortcode' );
		}

		$hop->alpha = round( $hop->alpha, 1 );

		return <<<HOP
		<tr>
			<td>$hop->name</td>
			<td>$hop->amount $t_weight</td>
			<td>$hop->time $t_time</td>
			<td>$hop->use</td>
			<td>$hop->form</td>
			<td>$hop->alpha</td>
		</tr>
HOP;
	}

	/**
	 * Build misc row
	 * @param  BeerXML_Misc         hop misc to display
	 * @return string               table row containing hop details
	 */
	static function build_misc( $misc ) {
		if ( $misc->time >= 1440 ) {
			$misc->time = round( $misc->time / 1440, 1);
			$t_time = _n( 'day', 'days', $misc->time, 'beerxml-shortcode' );
		} else {
			$misc->time = round( $misc->time );
			$t_time = __( 'min', 'beerxml-shortcode' );
		}

		return <<<MISC
		<tr>
			<td>$misc->name</td>
			<td>$misc->display_amount</td>
			<td>$misc->time $t_time</td>
			<td>$misc->use</td>
			<td>$misc->type</td>
		</tr>
MISC;
	}

	/**
	 * Build yeast row
	 * @param  BeerXML_Yeast        $yeast yeast to display
	 * @param  boolean $metric      true to display values in metric
	 * @return string               table row containing yeast details
	 */
	static function build_yeast( $yeast, $metric = false ) {
		if ( $metric ) {
			$yeast->min_temperature = round( $yeast->min_temperature, 2 );
			$yeast->max_temperature = round( $yeast->max_temperature, 2 );
			$t_temp = __( 'C', 'beerxml-shortcode' );
		} else {
			$yeast->min_temperature = round( ( $yeast->min_temperature * (9/5) ) + 32, 1 );
			$yeast->max_temperature = round( ( $yeast->max_temperature * (9/5) ) + 32, 1 );
			$t_temp = __( 'F', 'beerxml-shortcode' );
		}

		$yeast->attenuation = round( $yeast->attenuation );

		return <<<YEAST
		<tr>
			<td>$yeast->name ({$yeast->product_id})</td>
			<td>$yeast->laboratory</td>
			<td>{$yeast->attenuation}%</td>
			<td>{$yeast->min_temperature}°$t_temp - {$yeast->max_temperature}°$t_temp</td>
		</tr>
YEAST;
	}
}

// The fun starts here!
new BeerXML_Shortcode();

class BeerXML_Admin {
	/**
	 * Add options page to the admin menu and init the options
	 */
	function __construct() {
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'admin_init', array( $this, 'options_init' ) );
	}

	/**
	 * Add the options page
	 */
	function add_options_page() {
		add_options_page(
			'BeerXML Shortcode',
			'BeerXML Shortcode',
			'manage_options',
			'beerxml-shortcode',
			array( $this, 'options_page' )
		);
	}

	/**
	 * Output the options to screen
	 */
	function options_page() {
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>BeerXML Shortcode Settings</h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'beerxml_shortcode_group' );
				do_settings_sections( 'beerxml-shortcode' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Add the register the settings and setting sections
	 */
	function options_init() {
		register_setting( 'beerxml_shortcode_group', 'beerxml_shortcode_units', 'absint' );
		register_setting( 'beerxml_shortcode_group', 'beerxml_shortcode_cache', 'absint' );
		register_setting( 'beerxml_shortcode_group', 'beerxml_shortcode_download', 'absint' );
		register_setting( 'beerxml_shortcode_group', 'beerxml_shortcode_style', 'absint' );

		add_settings_section(
			'beerxml_shortcode_section',
			__( 'Shortcode default settings', 'beerxml-shortcode' ),
			array( $this, 'print_section_info' ),
			'beerxml-shortcode'
		);

		add_settings_field(
			'beerxml_shortcode_units',
			__( 'Units', 'beerxml-shortcode' ),
			array( $this, 'units_option' ),
			'beerxml-shortcode',
			'beerxml_shortcode_section'
		);

		add_settings_field(
			'beerxml_shortcode_cache',
			__( 'Cache duration (seconds)', 'beerxml-shortcode' ),
			array( $this, 'cache_option' ),
			'beerxml-shortcode',
			'beerxml_shortcode_section'
		);

		add_settings_field(
			'beerxml_shortcode_download',
			__( 'Include download link', 'beerxml-shortcode' ),
			array( $this, 'download_option' ),
			'beerxml-shortcode',
			'beerxml_shortcode_section'
		);

		add_settings_field(
			'beerxml_shortcode_style',
			__( 'Include style details', 'beerxml-shortcode' ),
			array( $this, 'style_option' ),
			'beerxml-shortcode',
			'beerxml_shortcode_section'
		);
	}

	/**
	 * Notice for default options
	 */
	function print_section_info() {
		_e( 'Used by default unless overwritten via shortcode', 'beerxml-shortcode' );
	}

	/**
	 * Callback for units option
	 */
	function units_option() {
		$units = get_option( 'beerxml_shortcode_units', 1 );
		?>
		<select id="beerxml_shortcode_units" name="beerxml_shortcode_units">
			<option value="1" <?php selected( $units, 1 ); ?>>US</option>
			<option value="2" <?php selected( $units, 2 ); ?>>Metric</option>
		</select>
		<?php
	}

	/**
	 * Callback for cache option
	 */
	function cache_option() {
		?>
		<input type="text" id="beerxml_shortcode_cache" name="beerxml_shortcode_cache" value="<?php echo get_option( 'beerxml_shortcode_cache', 60*60*12 ); ?>" />
		<?php
	}

	/**
	 * Callback for download option
	 */
	function download_option() {
		?>
		<input type="checkbox" id="beerxml_shortcode_download" name="beerxml_shortcode_download" value="1" <?php checked( get_option( 'beerxml_shortcode_download', 1 ) ); ?> />
		<?php
	}

	/**
	 * Callback for style option
	 */
	function style_option() {
		?>
		<input type="checkbox" id="beerxml_shortcode_style" name="beerxml_shortcode_style" value="1" <?php checked( get_option( 'beerxml_shortcode_style', 1 ) ); ?> />
		<?php
	}
}

// init admin
new BeerXML_Admin();

class BeerXML {
	public $recipes = array();

	function __construct( $xml_loc ) {
		if ( ! url_exists( $xml_loc ) )
			return;

		libxml_disable_entity_loader();
		libxml_use_internal_errors( true );
		$xml = file_get_contents( $xml_loc );
		$xrecipes = simplexml_load_string( $xml );
		if ( ! $xrecipes )
			return;

		foreach ( $xrecipes->RECIPE as $recipe ) {
			$this->recipes[] = new BeerXML_Recipe( $recipe );
		}
	}
}

class BeerXML_Recipe {
	public $name;
	public $version;
	public $type;
	public $style;
	public $equipment;
	public $brewer;
	public $asst_brewer;
	public $batch_size;
	public $boil_size;
	public $boil_time;
	public $efficiency;
	public $hops = array();
	public $fermentables = array();
	public $miscs = array();
	public $yeasts = array();
	public $waters = array();
	public $mash;
	public $notes;
	public $taste_notes;
	public $taste_rating;
	public $og;
	public $fg;
	public $fermentation_stages;
	public $primary_age;
	public $primary_temp;
	public $secondary_age;
	public $secondary_temp;
	public $tertiary_age;
	public $tertiary_temp;
	public $age;
	public $age_temp;
	public $date;
	public $carbonation;
	public $forced_carbonation;
	public $priming_sugar_name;
	public $carbonation_temp;
	public $priming_sugar_equiv;
	public $keg_priming_factor;
	public $est_og;
	public $est_fg;
	public $est_color;
	public $ibu;
	public $ibus;
	public $ibu_method;
	public $est_abv;
	public $abv;
	public $actual_efficiency;
	public $calories;
	public $display_batch_size;
	public $display_boil_size;
	public $display_og;
	public $display_fg;
	public $display_primary_temp;
	public $display_secondary_temp;
	public $display_tertiary_temp;
	public $display_age_temp;
	public $carbonation_used;
	public $display_carb_temp;

	function __construct( $recipe ) {
		$skip = array( 'HOPS', 'FERMENTABLES', 'MISCS', 'YEASTS', 'WATERS' );

		if( $recipe->HOPS->HOP ) {
			foreach ( $recipe->HOPS->HOP as $hop ) {
				$this->hops[] = new BeerXML_Hop( $hop );
			}
		}

		if( $recipe->FERMENTABLES->FERMENTABLE ) {
			foreach ( $recipe->FERMENTABLES->FERMENTABLE as $fermentable ) {
				$this->fermentables[] = new BeerXML_Fermentable( $fermentable );
			}
		}

		if( $recipe->MISCS->MISC ) {
			foreach ( $recipe->MISCS->MISC as $misc ) {
				$this->miscs[] = new BeerXML_Misc( $misc );
			}
		}

		if( $recipe->YEASTS->YEAST ) {
			foreach ( $recipe->YEASTS->YEAST as $yeast ) {
				$this->yeasts[] = new BeerXML_Yeast( $yeast );
			}
		}

		if( $recipe->WATERS->WATER ) {
			foreach ( $recipe->WATERS->WATER as $water ) {
				$this->waters[] = new BeerXML_Water( $water );
			}
		}

		foreach ( $recipe as $k => $v ) {
			if ( in_array( $k, $skip ) ) {
				continue;
			} else if ( 'STYLE' == $k ) {
				$this->{strtolower( $k )} = new BeerXML_Style( $v );
			} else if ( 'EQUIPMENT' == $k ) {
				$this->{strtolower( $k )} = new BeerXML_Equipment( $v );
			} else if ( 'MASH' == $k ) {
				$this->{strtolower( $k )} = new BeerXML_Mash_Profile( $v );
			} else {
				$this->{strtolower( $k )} = esc_html( (string)$v );
			}
		}
	}
}

class BeerXML_Hop {
	public $name;
	public $version;
	public $alpha;
	public $amount;
	public $use;
	public $time;
	public $notes;
	public $type;
	public $form;
	public $beta;
	public $hsi;
	public $origin;
	public $substitutes;
	public $humulene;
	public $caryophyllene;
	public $cohumulone;
	public $myrcene;

	function __construct( $hop ) {
		foreach ( $hop as $k => $v ) {
			$this->{strtolower( $k )} = esc_html( (string)$v );
		}
	}
}


class BeerXML_Fermentable {
	public $name;
	public $version;
	public $type;
	public $amount;
	public $yield;
	public $color;
	public $add_after_boil;
	public $origin;
	public $supplier;
	public $notes;
	public $coarse_fine_diff;
	public $moisture;
	public $diastatic_power;
	public $protein;
	public $max_in_batch;
	public $recommend_mash;

	function __construct( $fermentable ) {
		foreach ( $fermentable as $k => $v ) {
			$this->{strtolower( $k )} = esc_html( (string)$v );
		}
	}

	public static function calculate_total( array $fermentables ) {
		$total = 0;
		foreach ( $fermentables as $fermentable ) {
			$total += $fermentable->amount;
		}

		return $total;
	}

	public function percentage( $total ) {
		return ( $this->amount / $total ) * 100;
	}
}


class BeerXML_Yeast {
	public $name;
	public $version;
	public $type;
	public $form;
	public $amount;
	public $amount_is_weight;
	public $laboratory;
	public $product_id;
	public $min_temperature;
	public $max_temperature;
	public $flocculation;
	public $attenuation;
	public $notes;
	public $best_for;
	public $times_cultured;
	public $max_reuse;
	public $add_to_secondary;

	function __construct( $yeast ) {
		foreach ( $yeast as $k => $v ) {
			$this->{strtolower( $k )} = esc_html( (string)$v );
		}
	}
}


class BeerXML_Misc {
	public $name;
	public $version;
	public $type;
	public $use;
	public $time;
	public $amount;
	public $amount_is_weight;
	public $use_for;
	public $notes;
	public $water;

	function __construct( $misc ) {
		foreach ( $misc as $k => $v ) {
			$this->{strtolower( $k )} = esc_html( (string)$v );
		}
	}
}


class BeerXML_Water {
	public $name;
	public $version;
	public $amount;
	public $calcium;
	public $bicarbonate;
	public $sulfate;
	public $chloride;
	public $sodium;
	public $magnesium;
	public $ph;
	public $notes;

	function __construct( $water ) {
		foreach ( $water as $k => $v ) {
			$this->{strtolower( $k )} = esc_html( (string)$v );
		}
	}
}


class BeerXML_Equipment {
	public $name;
	public $version;
	public $boil_size;
	public $batch_size;
	public $tun_volume;
	public $tun_weight;
	public $tun_specific_heat;
	public $top_up_water;
	public $trub_chiller_loss;
	public $evap_rate;
	public $boil_time;
	public $calc_boil_volume;
	public $lauter_deadspace;
	public $top_up_kettle;
	public $hop_utilization;
	public $notes;

	function __construct( $equipment ) {
		foreach ( $equipment as $k => $v ) {
			$this->{strtolower( $k )} = esc_html( (string)$v );
		}
	}
}


class BeerXML_Style {
	public $name;
	public $category;
	public $version;
	public $category_number;
	public $style_letter;
	public $style_guide;
	public $type;
	public $og_min;
	public $og_max;
	public $fg_min;
	public $fg_max;
	public $ibu_min;
	public $ibu_max;
	public $color_min;
	public $color_max;
	public $carb_min;
	public $carb_max;
	public $abv_min;
	public $abv_max;
	public $notes;
	public $profile;
	public $ingredients;
	public $examples;

	function __construct( $style ) {
		foreach ( $style as $k => $v ) {
			$this->{strtolower( $k )} = esc_html( (string)$v );
		}
	}
}


class BeerXML_Mash_Step {
	public $name;
	public $version;
	public $type;
	public $infuse_amount;
	public $step_temp;
	public $step_time;
	public $ramp_time;
	public $end_temp;

	function __construct( $mash_step ) {
		foreach ( $mash_step as $k => $v ) {
			$this->{strtolower( $k )} = esc_html( (string)$v );
		}
	}
}


class BeerXML_Mash_Profile {
	public $name;
	public $version;
	public $grain_temp;
	public $mash_steps = array();
	public $notes;
	public $tun_temp;
	public $sparge_temp;
	public $ph;
	public $tun_weight;
	public $tun_specific_heat;
	public $equip_adjust;

	function __construct( $mash_profile ) {
		if ( $mash_profile->MASH_STEPS->MASH_STEP ) {
			foreach ( $mash_profile->MASH_STEPS->MASH_STEP as $mash_step ) {
				$this->mash_steps[] = new BeerXML_Mash_Step( $mash_step );
			}
		}

		foreach ( $mash_profile as $k => $v ) {
			if ( 'MASH_STEPS' != $k ) {
				$this->{strtolower( $k )} = esc_html( (string)$v );
			}
		}
	}
}

if ( ! function_exists( 'url_exists' ) ) :

	function url_exists( $url ) {
		$file_headers = @get_headers( $url );
		return false === strpos( $file_headers[0], '404' );;
	}

endif;

class BeerXML_Mime {
	/**
	 * Add a filter to upload_mimes
	 */
	function __construct() {
		add_filter( 'upload_mimes', array( $this, 'beerxml_mimes' ) );
	}

	/**
	 * Add mimes required for the BeerXML documents (currently just text/xml)
	 * @param  array $mimes mime types to filter
	 * @return array new array of acceptable mimes
	 */
	function beerxml_mimes( $mimes ) {
		if ( ! isset( $mimes['xml'] ) )
			return array_merge( $mimes, array( 'xml' => 'text/xml' ) );

		return $mimes;
	}
}

new BeerXML_Mime();

?>