<?php

namespace Drupal\Tests\draggable_mapper\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base class for JavaScript-based tests of the draggable mapper module.
 */
abstract class DraggableMapperJsTestBase extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'draggable_mapper',
    'node',
    'field',
    'field_ui',
    'file',
    'image',
    'inline_entity_form',
    'user',
    'system',
    'filter',
  ];

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * The admin user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Creates a test node.
   *
   * @param string $title
   *   The title of the node.
   * @param string $type
   *   The node type.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node.
   */
  protected function createTestNode($title, $type = 'article') {
    $node = $this->drupalCreateNode([
      'type' => $type,
      'title' => $title,
    ]);
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create admin user with necessary permissions
    $this->adminUser = $this->drupalCreateUser([
      'administer content types',
      'administer nodes',
      'administer node fields',
      'administer node display',
      'access content',
      'create article content',
      'edit any article content',
      'delete any article content',
    ]);
    $this->drupalLogin($this->adminUser);
  }
}
