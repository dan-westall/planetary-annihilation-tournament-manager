<?php get_header(); ?>

<div class="container">

    <article>

        <div class="row">

            <div class="col-lg-12">

                <header class="post-header container-box">

                    <h1 class="post-title"><?php the_title(); ?></h1>

                    <ul class="page-sub-menu">
                        <?php

                        foreach (Planetary_Annihilation_Tournament_Manager::$endpoints as $tournament_endpoint): ?>

                            <li>
                                <a href="<?php the_permalink(); ?>/<?php echo $tournament_endpoint; ?>"><?php echo ucwords($tournament_endpoint); ?></a>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </header>

            </div>

        </div>

        <div id="content-wrapper" class="clearfix content-wrapper row">

            <?php while (have_posts()) : the_post(); ?>

                <div id="post-<?php the_ID(); ?>" <?php post_class('col-lg-6'); ?>  role="main">

                    <div class="content-container container-box">

                        <div class="body text">

                            <?php $form_short_code = sprintf('[gravityform id="%s" name="Tournament Signup" title="false" description="false"]', get_field('signup_form'));

                            echo do_shortcode($form_short_code); ?>

                        </div>

                    </div>

                </div>

            <?php endwhile; ?>

            <aside role="complementary" class="col-lg-6">

                <?php get_sidebar('1'); ?>

            </aside>

        </div>

    </article>

</div>

<?php get_footer(); ?>



