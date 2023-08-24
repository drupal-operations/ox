<?php

namespace Drupal\site\Plugin\SiteProperty;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\site\SitePropertyPluginBase;

/**
 * Plugin implementation of the site_property.
 *
 * @SiteProperty(
 *   id = "git_reference",
 *   name = "git_reference",
 *   label = @Translation("Git Reference"),
 *   description = @Translation("The git branch or tag the site is running."),
 *   site_bundles = {
 *     "Drupal\site\Entity\Bundle\WebAppSiteBundle"
 *   },
 * )
 */
class GitReference extends SitePropertyPluginBase {

  /**
   * Load git branch or tag from .git directory, if it exists.
   * @return array|false|mixed|string|string[]|void
   */
  public function value() {
    if (getenv('DRUPAL_SITE_GIT_REFERENCE')) {
      return getenv('DRUPAL_SITE_GIT_REFERENCE');
    }

    $git_file = \Drupal::root() . '/../.git/HEAD';
    if (file_exists($git_file)) {
      $git_head = file_get_contents($git_file);

      // If "ref: ", it's a branch.
      if (strpos($git_head, 'ref: ') === 0) {
        $branch = str_replace('ref: refs/heads/', '', $git_head);
        return $branch;
      }
      elseif (!empty($git_head)) {
        // Find the tag
        $git_tags = \Drupal::root() . '/../.git/refs/tags';
        $files = \Drupal::service('file_system')->scanDirectory($git_tags, '/./');
        foreach ($files as $filename => $file_data) {
          if (file_get_contents($filename) == $git_head){
            return $file_data->filename;
          }
        }
      }
    }
  }

  /**
   * Define the Git Reference field.
   *
   * @return static
   *   A new field definition object.
   */
  static public function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields['git_reference'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Git Reference'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
    ;
    return $fields;
  }
}
