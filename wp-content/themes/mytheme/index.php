<?php
get_header();
?>
    <div class="main-content w-100 h-100">
        <?php
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                the_content();
            }
        }
        ?>
    </div>
<?php

get_footer();