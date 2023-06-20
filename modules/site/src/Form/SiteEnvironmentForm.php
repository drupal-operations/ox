<?php

namespace Drupal\site\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the site environment entity edit forms.
 */
class SiteEnvironmentForm extends ContentEntityForm {

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
        $this->messenger()->addStatus($this->t('New site environment %label has been created.', $message_arguments));
        $this->logger('site')->notice('Created new site environment %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The site environment %label has been updated.', $message_arguments));
        $this->logger('site')->notice('Updated site environment %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.site_environment.canonical', ['site_environment' => $entity->id()]);

    return $result;
  }

}
