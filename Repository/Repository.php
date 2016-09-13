<?php
namespace FS\SolrBundle\Repository;

use FS\SolrBundle\Doctrine\Hydration\HydrationModes;
use FS\SolrBundle\Query\FindByDocumentNameQuery;
use FS\SolrBundle\Query\FindByIdentifierQuery;
use FS\SolrBundle\Solr;

/**
 * Common repository class to find documents in the index
 */
class Repository implements RepositoryInterface
{

    /**
     * @var Solr
     */
    protected $solr = null;

    /**
     * @var object
     */
    protected $entity = null;

    /**
     * @var string
     */
    protected $hydrationMode = '';

    /**
     * @param Solr   $solr
     *
     * @param object $entity
     */
    public function __construct(Solr $solr, $entity)
    {
        $this->solr = $solr;
        $this->entity = $entity;

        $this->hydrationMode = HydrationModes::HYDRATE_DOCTRINE;
    }

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        $mapper = $this->solr->getMapper();
        $mapper->setMappingCommand($this->solr->getCommandFactory()->get('all'));
        $metaInformation = $this->solr->getMetaFactory()->loadInformation($this->entity);
        $metaInformation->setEntityId($id);

        $document = $mapper->toDocument($metaInformation);

        $query = new FindByIdentifierQuery();
        $query->setIndex($metaInformation->getIndex());
        $query->setDocumentKey($metaInformation->getDocumentKey());
        $query->setDocument($document);
        $query->setEntity($this->entity);
        $query->setSolr($this->solr);
        $query->setHydrationMode($this->hydrationMode);
        $found = $this->solr->query($query);

        if (count($found) == 0) {
            return null;
        }

        return array_pop($found);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        $mapper = $this->solr->getMapper();
        $mapper->setMappingCommand($this->solr->getCommandFactory()->get('all'));
        $metaInformation = $this->solr->getMetaFactory()->loadInformation($this->entity);

        $document = $mapper->toDocument($metaInformation);

        if (null === $document) {
            return null;
        }

        $document->removeField('id');

        $query = new FindByDocumentNameQuery();
        $query->setRows(1000000);
        $query->setDocumentName($metaInformation->getDocumentName());
        $query->setIndex($metaInformation->getIndex());
        $query->setDocument($document);
        $query->setEntity($this->entity);
        $query->setSolr($this->solr);
        $query->setHydrationMode($this->hydrationMode);

        return $this->solr->query($query);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $args)
    {
        $metaInformation = $this->solr->getMetaFactory()->loadInformation($this->entity);

        $query = $this->solr->createQuery($this->entity);
        $query->setHydrationMode($this->hydrationMode);
        $query->setRows(100000);
        $query->setUseAndOperator(true);
        $query->addSearchTerm('id', $metaInformation->getDocumentName(). '_*');
        $query->setQueryDefaultField('id');

        $helper = $query->getHelper();
        foreach ($args as $fieldName => $fieldValue) {
            $fieldValue = $helper->escapeTerm($fieldValue);

            $query->addSearchTerm($fieldName, $fieldValue);
        }

        return $this->solr->query($query);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $args)
    {
        $metaInformation = $this->solr->getMetaFactory()->loadInformation($this->entity);

        $query = $this->solr->createQuery($this->entity);
        $query->setHydrationMode($this->hydrationMode);
        $query->setRows(1);
        $query->setUseAndOperator(true);
        $query->addSearchTerm('id', $metaInformation->getDocumentName(). '_*');
        $query->setQueryDefaultField('id');

        $helper = $query->getHelper();
        foreach ($args as $fieldName => $fieldValue) {
            $fieldValue = $helper->escapeTerm($fieldValue);

            $query->addSearchTerm($fieldName, $fieldValue);
        }

        $found = $this->solr->query($query);

        return array_pop($found);
    }

    /**
     * {@inheritdoc}
     */
    public function createQuery()
    {
        return $this->solr->createQuery($this->entity);
    }
}
