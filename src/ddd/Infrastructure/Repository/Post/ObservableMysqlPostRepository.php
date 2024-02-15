<?php

namespace pascualmg\reactor\ddd\Infrastructure\Repository\Post;

use pascualmg\reactor\ddd\Domain\Entity\Post;
use pascualmg\reactor\ddd\Domain\Entity\PostRepository;
use React\Mysql\MysqlClient;
use React\Mysql\MysqlResult;
use React\Promise\PromiseInterface;
use Rx\Observable;

class ObservableMysqlPostRepository implements PostRepository
{
    private MysqlClient $mysqlClient;

    public function __construct(MysqlClient $mysqlClient)
    {
        $this->mysqlClient = $mysqlClient;
    }

    public function findAll(): PromiseInterface //of an array of Posts
    {
        $promiseOfQuery = $this->mysqlClient->query('SELECT * FROM post ');

        return Observable::fromPromise($promiseOfQuery)
            ->map(function (MysqlResult $mysqlResult) {
               return array_map([self::class, 'hydrate'], $mysqlResult->resultRows ) ;
            })
            ->toPromise();
    }

    public function findById(int $postId): PromiseInterface //of Post or Null
    {

        $promiseOfQuery =  $this->mysqlClient->query(
            "SELECT * FROM post where post.id = ?",
            [$postId]
        );

        return Observable::fromPromise($promiseOfQuery)
            ->map(fn (MysqlResult $mysqlResult) => self::hydrateOrNull($mysqlResult->resultRows[0] ??  null))
            ->toPromise();
    }

    private static function hydrateOrNull(?array $maybeResultRow): ?Post
    {
        if (null === $maybeResultRow) {
            return null;
        }
        return self::hydrate($maybeResultRow);
    }

    private static function hydrate(array $rawPost): Post
    {
        return new Post(
            $rawPost['id'],
            $rawPost['headline'] ?? "",
            $rawPost['articleBody'] ?? "",
            $rawPost['image'] ?? "",
            $rawPost['author'] ?? "",
            new \DateTimeImmutable($rawPost['datePublished'])
        );
    }
}
