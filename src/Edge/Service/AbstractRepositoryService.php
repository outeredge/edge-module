<?php

namespace Edge\Service;

use Edge\Entity\Repository\RepositoryInterface;
use Edge\Entity\AbstractEntity;
use Edge\Exception\RuntimeException;
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
            throw new RuntimeException('No repository is set on this service');
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
            throw new RuntimeException('No form instance is available');
        }
        $this->getEventManager()->trigger(__FUNCTION__, $this, array('form' => $this->form));
        return $this->form;
    }

    /**
     * @return Form
     */
    public function getEditForm()
    {
        return $this->getForm();
    }

    /**
     * Get a single entity by id using repositories find()
     *
     * @param  mixed $id
     * @return AbstractEntity
     * @throws Exception\EntityNotFoundException
     */
    protected function get($id)
    {
        $entity = $this->getRepository()->find($id);

        if (null === $entity) {
            throw new Exception\EntityNotFoundException;
        }

        return $entity;
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
            throw new Exception\CreationException('Unable to create entity', 422, null, $this->getErrorMessages());
        }

//        $n = new \Exception;

        $this->save($result, false);

        return $result;
    }

    /**
     * Update entity from array via form,
     * note that related entities cannot be updated in this manner
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

        if ($form->getValidationGroup()) {
            $validationGroup = array();
            $empty = true;

            foreach ($form->getValidationGroup() as $fieldset => $fields) {
                if (isset($values[$fieldset])) {
                    foreach ($fields as $key => $field) {
                        if (is_int($key)) {
                            $validationGroup[$fieldset][$field] = $key;
                        }
                    }

                    $validationGroup[$fieldset] = array_flip(array_intersect_key($validationGroup[$fieldset], $values[$fieldset]));

                    // don't update data that has not changed
                    foreach ($validationGroup[$fieldset] as $key => $field) {
                        if ($entity[$field] && $entity[$field] === $values[$fieldset][$field]) {
                            unset($validationGroup[$fieldset][$key]);
                        }
                    }
                }

                if (!empty($validationGroup[$fieldset])) {
                    $empty = false;
                }
            }

            // nothing to be updated, return the entity as it was
            if ($empty) {
                return $entity;
            }

            $form->setValidationGroup($validationGroup);
        }

        $result = $this->hydrate($entity, $values, $form);

        if (!$result instanceof AbstractEntity) {
            throw new Exception\UpdateException('Unable to update entity', 422, null, $this->getErrorMessages());
        }

        $this->getRepository()->update($result);
        return $result;
    }

    /**
     * Save an entity back to the database
     *
     * @param AbstractEntity|null $entity [optional]
     * @param bool $immediate or delay
     */
    protected function save(AbstractEntity $entity = null, $immediate = true)
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
            throw new RuntimeException('Unable to retrieve entity from Form');
        }

        return $result;
    }

    /**
     * Delete an entity
     *
     * @param  AbstractEntity $entity
     * @param  boolean $immediate
     * @throws Exception\DeleteException
     */
    protected function delete(AbstractEntity $entity, $immediate = true)
    {
        $this->getRepository()->delete($entity, $immediate);
        return $this;
    }
}