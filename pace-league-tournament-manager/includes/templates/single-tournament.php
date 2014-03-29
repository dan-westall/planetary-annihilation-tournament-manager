<?php get_header(); ?>

<div id="content-wrapper"  class="clearfix content-wrapper row">

    <?php while ( have_posts() ) : the_post(); ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class('col-lg-5'); ?>  role="main">

            <div class="content-container container-box">

                <header class="post-header">

                    <h1 class="post-title"><?php the_title(); ?></h1>

                </header>

                <div class="body text">

                    <?php the_content(); ?>


                </div>



            </div>

        </article>

    <?php endwhile; ?>

    <aside role="complementary">

        <?php get_sidebar('1'); ?>

    </aside>

</div>

<?php get_footer(); ?>

