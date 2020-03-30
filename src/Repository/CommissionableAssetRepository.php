<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CommissionableAsset;
use App\Entity\CommissionableAssetAvailability;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;

/**
 * @method CommissionableAsset|null find($id, $lockMode = null, $lockVersion = null)
 * @method CommissionableAsset|null findOneBy(array $criteria, array $orderBy = null)
 * @method CommissionableAsset[]    findAll()
 * @method CommissionableAsset[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommissionableAssetRepository extends ServiceEntityRepository implements AvailabilitableRepositoryInterface
{
    use AvailabilityQueryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommissionableAsset::class);
    }

    public function findByIds(array $ids): array
    {
        return $this->findBy(['id' => $ids]);
    }

    /**
     * @return CommissionableAsset[]|int[]
     */
    public function findByFilters(array $formData, bool $onlyIds = false): array
    {
        $qb = $this->createQueryBuilder('a');

        if ($onlyIds) {
            $qb->select('a.id');
        }

        if (\count($formData['assetTypes'] ?? []) > 0) {
            $qb->andWhere('a.type IN (:types)')->setParameter('types', $formData['assetTypes']);
        }

        if (\count($formData['organizations'] ?? []) > 0) {
            $qb->andWhere('a.organization IN (:organisations)')->setParameter('organisations', $formData['organizations']);
        }

        if (!empty($formData['availableFrom']) && !empty($formData['availableTo'])) {
            $qb = $this->addAvailabilityBetween($qb, $formData['availableFrom'], $formData['availableTo'], CommissionableAssetAvailability::class, 'asset');
        }

        $qb->orderBy('a.name');

        return $qb
            ->getQuery()
            ->getResult($onlyIds ? AbstractQuery::HYDRATE_SCALAR : AbstractQuery::HYDRATE_OBJECT);
    }
}
