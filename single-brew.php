<div class"row">
  <div class="col-sm-12">
    <h1 class="page-title">
      <?php	the_title(); ?> - <span class="label label-info"><?php the_field('label'); ?></span>
    </h1>
    <hr>
	</div>
</div>

<div class="row">
  <div class="col-sm-8">
    <table class="table table-striped">
      <tr>
        <td>Style</td>
        <td><?php the_field('style'); ?></td>
      </tr>
      <tr>
        <td>ABV</td>
        <td><?php the_field('abv'); ?></td>
      </tr>
      <tr>
        <td>OG</td>
        <td><?php the_field('og'); ?></td>
      </tr>
      <tr>
        <td>FG</td>
        <td><?php the_field('fg'); ?></td>
      </tr>
      <tr>
        <td>IBUs</td>
        <td><?php the_field('ibu'); ?></td>
      </tr>
      <tr>
        <td>Color</td>
        <td><?php the_field('color'); ?></td>
      </tr>
      <tr>
        <td nowrap>Recipe Notes</td>
        <td><?php the_field('recipe_notes'); ?></td>
      </tr>
      <tr>
        <td nowrap>Batch Notes</td>
        <td><?php the_field('batch_notes'); ?></td>
      </tr>
    </table>
  </div>
  <div class="col-sm-4">
  <?php 
  if(get_field('image')) { 
    ?><img src=" <?php the_field('image'); ?> " class="img-responsive img-thumbnail img-rounded"><?php
  } else {
      echo '<h3>No image supplied</h3>';
  }  
  ?>
  </div>
</div>

<div class="row">
  <div class="col-sm-12">
    <?php
			if( get_field('beerxml') ) {
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				if( true ){ 
          $beerxml = "[beerxml recipe=" . get_field('beerxml') . ' metric=false download=true]';    			
          echo do_shortcode($beerxml);
        } else {
          echo '<a href="';
          the_field('beerxml');
          echo '">Download File</a>';
        }
			}	
		?>
  </div>
</div>