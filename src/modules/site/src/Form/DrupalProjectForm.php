<?php

namespace Drupal\site\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\site\Entity\SiteEntity;

/**
 * Form controller for the drupal project entity edit forms.
 */
class DrupalProjectForm extends ContentEntityForm {

  /**
   * @inheritdoc
   */
  public function form(array $form, FormStateInterface $form_state)
  {
    $form = parent::form($form, $form_state);
    $form['revision']['#type'] = 'value';
    $form['revision']['#value'] = TRUE;

    // Node author information for administrators.
    $form['author'] = [
      '#type' => 'details',
      '#title' => $this->t('Authoring information'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['node-form-author'],
      ],
      '#attached' => [
        'library' => ['node/drupal.node'],
      ],
      '#weight' => 90,
      '#optional' => TRUE,
    ];
    $form['created']['#group'] = 'author';
    $form['uid']['#group'] = 'author';
    $form['status']['#group'] = 'author';

    // Add help
    $items = [];
    $items[] = t('The Drupal Site UUID uniquely identifies each site across environments. It is used when exporting configuration.');
    $items[] = t("If you add Site.module to your sites, this UUID will be used to match sites to the correct Drupal project. Make sure the Site UUID here matches the one in your site. See <a href='https://www.drupal.org/docs/administering-a-drupal-site/configuration-management/managing-your-sites-configuration#s-drupal-site-uuid' target='_blank'>Drupal Site Configuration Documentation</a> for more instructions on setting your site UUID.</em>");
    $items[] = t('If your site already exists, you can retrieve the site UUID by running the command  <code>drush config:get system.site uuid</code>.');
    $items[] = t("Leave blank to generate a new site UUID. Remember to ensure the site UUIDs match.");

    $form['drupal_site_uuid']['widget'][0]['value']['#required'] = false;
    $form['drupal_site_uuid']['widget'][0]['value']['#description'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];

    $form['drupal_site_name']['widget'][0]['value']['#required'] = false;
    $form['drupal_site_name']['widget'][0]['value']['#description'] = [
      '#theme' => 'item_list',
      '#items' => [
        t('Leave blank to automatically detect the Drupal site name.'),
      ],
    ];

    $form['info'] = [
      '#type' => 'details',
      '#title' => t('Site Information'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['site-form-api'],
      ],
      '#weight' => 10,
      '#optional' => TRUE,
    ];

    $form['api_key']['#group'] = 'info';
    $form['api_key']['widget'][0]['value']['#description'] = t('If the site uses Site.module, enter an API key from the live site. Sites in this project will use this API key, unless they have one themselves.');

    $form['drupal_site_name']['#group'] = 'info';
    $form['git_remote']['#group'] = 'info';
    $form['drupal_site_uuid']['#group'] = 'info';

    return $form;
  }

  /**
   * Generates UUID for you.
   * @inheritdoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    if (empty($form_state->getValue('drupal_site_uuid')[0]['value'])) {
      $form_state->setValue('drupal_site_uuid', [[
        'value' => \Drupal::service('uuid')->generate()
      ]]);
    }

    // If there are no sites with the project's canonical URL, create one.
    // $canonical_site = SiteEntity::loadBySiteUrl($form_state->getValue('canonical_url')[0]['value']);

    // @TODO: Strange things happened when trying to match an existing site. Just create a new one.
//    if ($form_state->getValue('canonical_url') && !$canonical_site) {
    if ($form_state->getValue('canonical_url')) {
      $canonical_site = SiteEntity::create([
        'site_type' => 'drupal',
        'site_uri' => $form_state->getValue('canonical_url'),
        'drupal_project' => $form_state->getValue('drupal_site_uuid')[0]['value'],
        'drupal_site_uuid' => $form_state->getValue('drupal_site_uuid')[0]['value'],
      ]);
      $canonical_site->save();
    }
//    elseif ($canonical_site && empty($canonical_site->drupalProject())) {
//      $canonical_site->set('drupal_project', $form_state->getValue('drupal_site_uuid'));
//      $canonical_site->set('drupal_site_uuid', $form_state->getValue('drupal_site_uuid'));
//      $canonical_site->save();
//      \Drupal::messenger()->addStatus(t("The site entity @site with that canonical URL exists, so it was automatically added to the project.", [
//        '@site' => $canonical_site->toLink()->toString(),
//      ]));
//    }
//    elseif ($canonical_site) {
//      \Drupal::messenger()->addWarning(t('A site entity with a URL that matches the project canonical URL exists, but is already associated with the Drupal project @project. Check the site @link.', [
//        '@project' => $canonical_site->drupalProject()->toLink()->toString(),
//        '@link' => $canonical_site->toLink()->toString(),
//      ]));
//      $canonical_site->save();
//    }

    // Set drupalproject drupal site name from site.
    if ($canonical_site && empty($form_state->getValue('drupal_site_name')[0]['value']) && !empty($canonical_site->site_title->value)) {
      $form_state->setValue('drupal_site_name', [['value' => $canonical_site->site_title->value]]);
    }

    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New drupal project %label has been created.', $message_arguments));
        $this->logger('site')->notice('Created new drupal project %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The drupal project %label has been updated.', $message_arguments));
        $this->logger('site')->notice('Updated drupal project %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.drupal_project.canonical', ['drupal_project' => $entity->id()]);

    return $result;
  }

}
