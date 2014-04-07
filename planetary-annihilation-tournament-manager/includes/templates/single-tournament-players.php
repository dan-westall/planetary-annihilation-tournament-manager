<?php get_header(); ?>

<div class="container">

    <div id="content-wrapper"  class="clearfix content-wrapper row">

        <?php while ( have_posts() ) : the_post(); ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class('col-lg-12'); ?>  role="main">

                <div class="content-container">

                    <header class="post-header">

                        <h1 class="post-title"><?php the_title(); ?></h1>

                    </header>

                    <div class="body text">

                        <?php the_content(); ?>

                    </div>

                </div>

            </article>

        <?php endwhile; ?>

    </div>

</div>

<?php get_footer(); ?>

