<?php
if (!function_exists('newsper_front_page_banner_section')) :
  /**
   *
   * @since newsper
   *
   */
  function newsper_front_page_banner_section() {
    if (is_front_page() || is_home()) {
          
      $slider_post_order_by = newsair_get_option('slider_post_order_by','date-desc');
      $newsair_slider_category = newsair_get_option('select_slider_news_category');
      $newsair_number_of_post = newsair_get_option('newsair_number_of_post');
      $newsair_all_posts_main = newsair_get_posts($newsair_number_of_post, $newsair_slider_category, $slider_post_order_by);
      $meta_enable = get_theme_mod('slider_meta_enable','true');
      $newsair_count = 1;
      ?>
      <!--row-->
        <!-- Start Slider Post -->
        <div class="col-lg-6">
          <div class="mb-lg-0 mb-4">
            <div class="homemain bs swiper-container">
              <div class="swiper-wrapper">
                <?php get_template_part('inc/ansar/hooks/blocks/block','banner-list'); ?>
              </div>
              <!-- Add Arrows -->
              <div class="swiper-button-next"></div>
              <div class="swiper-button-prev"></div>

              <!-- <div class="swiper-pagination"></div> -->
            </div>
            <!--/swipper-->
          </div>
        </div>
        <!-- End Slider Post -->
        <!-- Start Editor Post -->
        <div class="col-lg-3">
          <div class="multi-post-widget mb-0">
              <?php
              $select_trending_news_category = newsair_get_option('select_trending_news_category');
              $trending_news_number = 2;
              $newsair_all_posts_main = newsair_get_posts($trending_news_number, $select_trending_news_category);
              if ($newsair_all_posts_main->have_posts()) :
                while ($newsair_all_posts_main->have_posts()) : $newsair_all_posts_main->the_post();
                global $post;
                $newsair_url = newsair_get_freatured_image_url($post->ID, 'newsair-slider-full');
                ?>
                  <div class="bs-blog-post three sm back-img bshre" <?php if (!empty($newsair_url)): ?>
                      style="background-image: url('<?php echo esc_url($newsair_url); ?>');"
                      <?php endif; ?>>
                    <a class="link-div" href="<?php the_permalink(); ?>"> </a>
                    <div class="inner">    
                      <?php if($meta_enable == true) {
                        newsair_post_categories(); ?>
                        <h4 class="title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                        <?php newsair_post_meta();
                      } else { ?>
                        <h4 class="title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                      <?php } ?>
                    </div>
                  </div> 
              <?php endwhile;
              endif;
            wp_reset_postdata(); ?>
            </div>
          </div>
        <!-- End Editor Post -->
        <!-- Start Recent Post --> 
          <div class="col-lg-3">
            <div class="bs-recent-blog-post">
            <?php
          $select_editor_news_category = newsair_get_option('select_editor_news_category');
          $editor_news_number = 4;
          $newsair_all_posts_main = newsair_get_posts($editor_news_number, $select_editor_news_category);
          if ($newsair_all_posts_main->have_posts()) :
            while ($newsair_all_posts_main->have_posts()) : $newsair_all_posts_main->the_post();
            global $post;
            $newsair_url = newsair_get_freatured_image_url($post->ID, 'newsair-slider-full'); ?>
			<div class="small-post">
				<?php if(!empty($newsair_url)){?>
				<div class="img-small-post back-img hlgr right">
					<a href="<?php the_permalink(); ?>" class="post-thumbnail"> 
            <img class="" src="<?php echo esc_url($newsair_url); ?>" alt="<?php the_title(); ?>">
          </a>
				</div>
				<?php } ?>
				<div class="small-post-content">
					<div class="bs-blog-meta">
						<?php newsair_date_content(); ?>
					</div>
					<h5 class="title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h5>
				</div>
			</div>
		<?php endwhile; 
			endif; ?>
      </div>
          </div>
        <!-- End Recent Post --> 
        
    <?php }
  }
endif;
add_action('newsper_action_front_page_main_section_1', 'newsper_front_page_banner_section', 40); 