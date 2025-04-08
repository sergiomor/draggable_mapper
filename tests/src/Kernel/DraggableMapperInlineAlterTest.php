<?php

namespace Drupal\Tests\draggable_mapper\Kernel;

use Drupal\Core\Form\FormState;

/**
 * Tests hook_inline_entity_form_entity_form_alter().
 *
 * @group draggable_mapper
 */
class DraggableMapperInlineAlterTest extends DraggableMapperKernelTestBase {

  /**
   * Tests that the inline entity form alter hook adds the preview container
   * and sets coordinate fields as hidden for marker paragraphs. 
   * Also tests if the librarry is attached.
   */
  public function testFormAlterHook() {

    // Create a Draggable Mapper entity.
    $title = "Test Hook Mapper";
    $draggable_mapper = $this->CreateEntity($title);

    // Simulate building the form.
    $form_object = \Drupal::entityTypeManager()
      ->getFormObject('draggable_mapper', 'default');
    $form_object->setEntity($draggable_mapper);
    $form_state = new FormState();
    $form = $form_object->buildForm([], $form_state);

    // Simulate the hook altering it.
    \draggable_mapper_inline_entity_form_entity_form_alter($form, $form_state);

    // Assert the library is attached.
    $this->assertArrayHasKey('#attached', $form);
    $this->assertContains(
      'draggable_mapper/draggable_mapper.form',
      $form['#attached']['library'],
      'draggable_mapper.form library is attached'
    );

    // Assert the preview container is added.
    $this->assertArrayHasKey('dme_preview_container', $form, 'Preview container added.');
    $this->assertEquals('dme-container', $form['dme_preview_container']['#attributes']['id'], 'Preview container HTML id is set correctly.');

    // Verify that the preview container includes the unmapped and container.
    $this->assertArrayHasKey('dme_unmapped_wrapper', $form['dme_preview_container']);
    $this->assertArrayHasKey('dme_container_wrapper', $form['dme_preview_container']);
    $form['field_dme_marker']['widget'][0]['subform']['field_dme_marker_x']['widget'][0]['value'] = [
      '#type' => 'hidden',
    ];
    
    // Confirm sets coordinate fields as hidden.
    $widget = $form['field_dme_marker']['widget'][0]['subform']['field_dme_marker_x']['widget'][0]['value'];
    $this->assertEquals('hidden', $widget['#type'], 'Field dme_marker_x is hidden.');
  }

}