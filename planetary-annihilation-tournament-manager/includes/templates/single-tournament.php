<?php get_header(); ?>

<div class="container">

    <div id="content-wrapper"  class="clearfix content-wrapper row">

        <div class="col-lg-2">

                <div class="container-box">
                    <ul class="content-sub-menu">
                    <?php

                    foreach(Planetary_Annihilation_Tournament_Manager::$endpoints as $tournament_endpoint): ?>

                        <li><a href="<?php the_permalink(); ?>/<?php echo $tournament_endpoint; ?>"><?php echo ucwords($tournament_endpoint); ?></a></li>

                    <?php endforeach; ?>

                    </ul>
                </div>

        </div>


        <?php while ( have_posts() ) : the_post(); ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class('col-lg-6'); ?>  role="main">

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

        <aside role="complementary" class="col-lg-4">

            <?php get_sidebar('1'); ?>

        </aside>

    </div>

</div>

<?php get_footer(); ?>

