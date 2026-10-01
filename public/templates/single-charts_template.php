<?php
/**
 * Template for displaying single Chart Templates inside the Elementor Editor.
 */
get_header();

while ( have_posts() ) :
    the_post();
    ?>
    <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="entry-content">
            <?php the_content(); ?>
        </div>
    </div>
    <?php
endwhile;

get_footer();
