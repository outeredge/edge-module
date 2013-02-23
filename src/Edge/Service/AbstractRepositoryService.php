<?php

namespace Edge\Service;

use Edge\Entity\Repository\RepositoryInterface;
use Edge\Entity\AbstractEntity;
use Zend\Form\Form;

abstract class AbstractRepositoryService extends AbstractBaseService
{
    /**
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * @var Form
     */
    protected $form;

    /**
     * Set the repository
     *
     * @param RepositoryInterface $repository
     */
    public function setRepository(RepositoryInterface $repository)
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * @return RepositoryInterface
     */
    public function getRepository()
    {
        if (null === $this->repository) {
            throw new \RuntimeException('No repository is set on the service');
        }
        return $this->repository;
    }

    /**
     * Set Form
     *
     * @param Form $form
     * @return AbstractRepositoryService
     */
    public function setForm(Form $form)
    {
        $this->form = $form;
        return $this;
    }

    /**
     * @return Form
     */
    public function getForm()
    {
        if (null === $this->form) {
            throw new \RuntimeException('No form instance is available');
        }
        $this->getEventManager()->trigger(__FUNCTION__, $this, array('form'=>$this->form));
        return $this->form;
    }

    public function getEditForm()
    {
        return $this->getForm();
    }

    /**
     * Create an entity from array via form
     *
     * @param AbstractEntity $entity
     * @param array $values
     * @param Form $form
     * @return AbstractEntity
     */
    protected function create(AbstractEntity $entity, array $values, Form $form = null)
    {
        if (null === $form) {
            $form = $this->getForm();
        }

        $result = $this->hydrate($entity, $values, $form);

        if (!$result instanceof AbstractEntity) {
            return false;
        }

        $this->save($result, false);

        return $result;
    }

    /**
     * Update entity from array via form
     *
     * @param AbstractEntity $entity
     * @param array $values
     * @param Form $form
     * @return AbstractEntity
     */
    protected function update(AbstractEntity $entity, array $values, Form $form = null)
    {
        if (null === $form) {
            $form = $this->getEditForm();
        }

        $validationGroup = $form->getValidationGroup();
        if ($validationGroup !== null) {
            foreach ($validationGroup as $fieldset => &$fields) {
                foreach ($fields as $key => $field) {
                    if (is_int($key)) {
                        unset($validationGroup[$fieldset][$key]);
                        $validationGroup[$fieldset][$field] = $key;
                    }
                }

                $validationGroup[$fieldset] = array_intersect_key($fields, $values[$fieldset]);

                foreach ($validationGroup[$fieldset] as $field => $elements) {
                    if (is_int($elements)) {
                        unset($validationGroup[$fieldset][$field]);
                        $validationGroup[$fieldset][$elements] = $field;
                    }
                }
            }
            $form->setValidationGroup($validationGroup);
        }

        $result = $this->hydrate($entity, $values, $form);

        if ($result instanceof AbstractEntity) {
            $this->getRepository()->update($result);
        }

        return $result;
    }

    /**
     * Save an entity back to the database
     *
     * @param AbstractEntity $entity
     * @param bool $immediate or delay
     */
    protected function save(AbstractEntity $entity, $immediate = true)
    {
        $this->getRepository()->save($entity, $immediate);
    }

    /**
     * Hydrate an array of data onto an entity using a form
     *
     * @param AbstractEntity $entity
     * @param array $values
     * @param Form $form
     * @return AbstractEntity
     */
    private function hydrate(AbstractEntity $entity, array $values, Form $form)
    {
        $form->setObject($entity);
        if ($form->getBaseFieldset()) {
            $form->getBaseFieldset()->setObject($entity);
        }

        $form->setData($values);

        if (!$form->isValid()) {
            return $this->setErrorMessages($form->getMessages());
        }

        $result = $form->getData();
        if (!$result instanceof AbstractEntity) {
            throw new \Exception('Unable to cast form to Entity');
        }

        return $result;
    }

    /**
     * Delete an entity
     *
     * @param AbstractEntity $entity
     */
    protected function delete(AbstractEntity $entity)
    {
        $this->getRepository()->delete($entity);
        return $this;
    }
}