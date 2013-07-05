<?php

namespace Edge\Entity\Repository;

use Edge\Entity\AbstractEntity;

interface RepositoryInterface
{
    public function save(AbstractEntity $entity = null);

    public function update(AbstractEntity $entity);

    public function delete(AbstractEntity $entity);
}