<?php
/**
 * Tests for the Query block rendering.
 *
 * @package WordPress
 * @subpackage Blocks
 * @since 6.0.0
 *
 * @group blocks
 */
class Tests_Blocks_RenderQueryBlock extends WP_UnitTestCase {

	private static $post_1;
	private static $post_2;
	private static $post_3;

	public function set_up() {
		parent::set_up();

		self::$post_1 = self::factory()->post->create_and_get(
			array(
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_name'    => 'post-1',
				'post_title'   => 'Post 1',
				'post_content' => 'Post 1 content',
				'post_excerpt' => 'Post 1',
			)
		);

		self::$post_2 = self::factory()->post->create_and_get(
			array(
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_name'    => 'post-2',
				'post_title'   => 'Post 2',
				'post_content' => 'Post 2 content',
				'post_excerpt' => 'Post 2',
			)
		);

		self::$post_3 = self::factory()->post->create_and_get(
			array(
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_name'    => 'post-2',
				'post_title'   => 'Post 2',
				'post_content' => 'Post 2 content',
				'post_excerpt' => 'Post 2',
			)
		);

		register_block_type(
			'test/plugin-block',
			array(
				'render_callback' => static function () {
					return '<div class="wp-block-test/plugin-block">Test</div>';
				},
			)
		);
	}

	public function tear_down() {
		unregister_block_type( 'test/plugin-block' );
		parent::tear_down();
	}

	/**
	 * Tests that the `core/query` block adds the corresponding directives when
	 * the `enhancedPagination` attribute is set.
	 */
	public function test_rendering_query_with_enhanced_pagination() {
		global $wp_query, $wp_the_query, $paged;

		$content = <<<HTML
		<!-- wp:query {"queryId":0,"query":{"inherit":true},"enhancedPagination":true} -->
		<div class="wp-block-query">
			<!-- wp:post-template {"align":"wide"} -->
			<!-- /wp:post-template -->
			<!-- wp:query-pagination -->
				<!-- wp:query-pagination-previous /-->
				<!-- wp:query-pagination-next /-->
			<!-- /wp:query-pagination -->
		</div>
		<!-- /wp:query -->
HTML;

		// Set main query to single post.
		$wp_query = new WP_Query(
			array(
				'posts_per_page' => 1,
				'paged'          => 2,
			)
		);

		$wp_the_query = $wp_query;
		$prev_paged   = $paged;
		$paged        = 2;

		$output = do_blocks( $content );

		$paged = $prev_paged;

		$p = new WP_HTML_Tag_Processor( $output );

		$p->next_tag( array( 'class_name' => 'wp-block-query' ) );
		$this->assertSame( '{"core":{"query":{"loadingText":"Loading page, please wait.","loadedText":"Page Loaded."}}}', $p->get_attribute( 'data-wp-context' ) );
		$this->assertSame( 'query-0', $p->get_attribute( 'data-wp-navigation-id' ) );
		$this->assertSame( true, $p->get_attribute( 'data-wp-interactive' ) );

		$p->next_tag( array( 'class_name' => 'wp-block-post' ) );
		$this->assertSame( 'post-template-item-' . self::$post_2->ID, $p->get_attribute( 'data-wp-key' ) );

		$p->next_tag( array( 'class_name' => 'wp-block-query-pagination-previous' ) );
		$this->assertSame( 'query-pagination-previous', $p->get_attribute( 'data-wp-key' ) );
		$this->assertSame( 'actions.core.query.navigate', $p->get_attribute( 'data-wp-on--click' ) );
		$this->assertSame( 'actions.core.query.prefetch', $p->get_attribute( 'data-wp-on--mouseenter' ) );
		$this->assertSame( 'effects.core.query.prefetch', $p->get_attribute( 'data-wp-effect' ) );

		$p->next_tag( array( 'class_name' => 'wp-block-query-pagination-next' ) );
		$this->assertSame( 'query-pagination-next', $p->get_attribute( 'data-wp-key' ) );
		$this->assertSame( 'actions.core.query.navigate', $p->get_attribute( 'data-wp-on--click' ) );
		$this->assertSame( 'actions.core.query.prefetch', $p->get_attribute( 'data-wp-on--mouseenter' ) );
		$this->assertSame( 'effects.core.query.prefetch', $p->get_attribute( 'data-wp-effect' ) );

		$p->next_tag( array( 'class_name' => 'screen-reader-text' ) );
		$this->assertSame( 'polite', $p->get_attribute( 'aria-live' ) );
		$this->assertSame( 'context.core.query.message', $p->get_attribute( 'data-wp-text' ) );

		$p->next_tag( array( 'class_name' => 'wp-block-query__enhanced-pagination-animation' ) );
		$this->assertSame( 'selectors.core.query.startAnimation', $p->get_attribute( 'data-wp-class--start-animation' ) );
		$this->assertSame( 'selectors.core.query.finishAnimation', $p->get_attribute( 'data-wp-class--finish-animation' ) );
	}

	/**
	 * Tests that the `core/query` block adds an extra attribute to disable the
	 * enhanced pagination in the browser when a plugin block is found inside.
	 */
	public function test_rendering_query_with_enhanced_pagination_auto_disabled_when_plugins_blocks_are_found() {
		global $wp_query, $wp_the_query;

		$content = <<<HTML
		<!-- wp:query {"queryId":0,"query":{"inherit":true},"enhancedPagination":true} -->
		<div class="wp-block-query">
			<!-- wp:post-template {"align":"wide"} -->
				<!-- wp:test/plugin-block /-->
			<!-- /wp:post-template -->
		</div>
		<!-- /wp:query -->
HTML;

		// Set main query to single post.
		$wp_query = new WP_Query(
			array(
				'posts_per_page' => 1,
			)
		);

		$wp_the_query = $wp_query;

		$output = do_blocks( $content );

		$p = new WP_HTML_Tag_Processor( $output );

		$p->next_tag( array( 'class_name' => 'wp-block-query' ) );
		$this->assertSame( 'query-0', $p->get_attribute( 'data-wp-navigation-id' ) );
		$this->assertSame( 'true', $p->get_attribute( 'data-wp-navigation-disabled' ) );
	}

	/**
	 * Tests that the `core/query` block adds an extra attribute to disable the
	 * enhanced pagination in the browser when a post content block is found inside.
	 */
	public function test_rendering_query_with_enhanced_pagination_auto_disabled_when_post_content_block_is_found() {
		global $wp_query, $wp_the_query;

		$content = <<<HTML
		<!-- wp:query {"queryId":0,"query":{"inherit":true},"enhancedPagination":true} -->
		<div class="wp-block-query">
			<!-- wp:post-template {"align":"wide"} -->
				<!-- wp:post-content /-->
			<!-- /wp:post-template -->
		</div>
		<!-- /wp:query -->
HTML;

		// Set main query to single post.
		$wp_query = new WP_Query(
			array(
				'posts_per_page' => 1,
			)
		);

		$wp_the_query = $wp_query;

		$output = do_blocks( $content );

		$p = new WP_HTML_Tag_Processor( $output );

		$p->next_tag( array( 'class_name' => 'wp-block-query' ) );
		$this->assertSame( 'query-0', $p->get_attribute( 'data-wp-navigation-id' ) );
		$this->assertSame( 'true', $p->get_attribute( 'data-wp-navigation-disabled' ) );
	}

	/**
	 * Tests that the correct `core/query` blocks get the attribute that
	 * disables enhanced pagination only if they contain a descendant that is
	 * not supported (i.e., a plugin block).
	 */
	public function test_rendering_nested_queries_with_enhanced_pagination_auto_disabled() {
		global $wp_query, $wp_the_query;

		$content = <<<HTML
			<!-- wp:query {"queryId":0,"query":{"inherit":true},"enhancedPagination":true} -->
			<div class="wp-block-query">
				<!-- wp:post-template {"align":"wide"} -->
					<!-- wp:query {"queryId":1,"query":{"inherit":true},"enhancedPagination":true} -->
					<div class="wp-block-query">
						<!-- wp:post-template {"align":"wide"} -->
						<!-- /wp:post-template -->
					</div>
					<!-- /wp:query-pagination -->
					<!-- wp:query {"queryId":2,"query":{"inherit":true},"enhancedPagination":true} -->
					<div class="wp-block-query">
						<!-- wp:post-template {"align":"wide"} -->
							<!-- wp:test/plugin-block /-->
						<!-- /wp:post-template -->
					</div>
					<!-- /wp:query-pagination -->
				<!-- /wp:post-template -->
			</div>
			<!-- /wp:query -->
HTML;

		// Set main query to single post.
		$wp_query = new WP_Query(
			array(
				'posts_per_page' => 1,
			)
		);

		$wp_the_query = $wp_query;

		$output = do_blocks( $content );

		$p = new WP_HTML_Tag_Processor( $output );

		// Query 0 contains a plugin block inside query-2 -> disabled.
		$p->next_tag( array( 'class_name' => 'wp-block-query' ) );
		$this->assertSame( 'query-0', $p->get_attribute( 'data-wp-navigation-id' ) );
		$this->assertSame( 'true', $p->get_attribute( 'data-wp-navigation-disabled' ) );

		// Query 1 does not contain a plugin block -> enabled.
		$p->next_tag( array( 'class_name' => 'wp-block-query' ) );
		$this->assertSame( 'query-1', $p->get_attribute( 'data-wp-navigation-id' ) );
		$this->assertSame( null, $p->get_attribute( 'data-wp-navigation-disabled' ) );

		// Query 2 contains a plugin block -> disabled.
		$p->next_tag( array( 'class_name' => 'wp-block-query' ) );
		$this->assertSame( 'query-2', $p->get_attribute( 'data-wp-navigation-id' ) );
		$this->assertSame( 'true', $p->get_attribute( 'data-wp-navigation-disabled' ) );
	}
}
