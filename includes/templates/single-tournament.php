<?php get_header(); ?>

<div class="container">

    <article>

        <div class="row">

            <div class="col-lg-12">

                <header class="post-header container-box">

                    <h1 class="post-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>

                    <ul class="page-sub-menu">

                        <?php echo tournamentCPT::tournament_menu(); ?>

                    </ul>

                </header>

            </div>

        </div>

        <div id="content-wrapper" class="clearfix content-wrapper row">

            <?php while (have_posts()) : the_post(); ?>

                <div id="post-<?php the_ID(); ?>" <?php post_class('col-lg-7'); ?>  role="main">

                    <div class="content-container container-box text">

                        <?php tournamentCPT::tournament_endpoint_sections(); ?>

                    </div>

                </div>

            <?php endwhile; ?>

            <aside role="complementary" class="col-lg-5">

                <?php get_sidebar('1'); ?>

            </aside>

        </div>

    </article>

</div>

<?php get_footer(); ?>

