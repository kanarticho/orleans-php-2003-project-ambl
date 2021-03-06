<?php

/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 07/03/18
 * Time: 18:20
 * PHP version 7
 */

namespace App\Model;

/**
 *
 */
class CatalogManager extends AbstractManager
{
    /**
     * Name of table
     */
    const TABLE = 'element';

    /**
     * Number of results to display
     */
    const MAX_RESULT = 12;

    /**
     *  Initializes this class.
     */
    public function __construct()
    {
        parent::__construct(self::TABLE);
    }

    /**
     * Get all row from database.
     *
     * @param string $search
     * @return array
     */
    public function selectAll(string $search = ''): array
    {
        $query = "SELECT " . self::TABLE . ".*, toxicity.name toxicity_name, element_type.name type_name
                    FROM " . self::TABLE . "
                    LEFT JOIN toxicity ON toxicity.id=element.toxicity_id
                    LEFT JOIN element_type ON element_type.id=element.element_type_id";

        if ($search) {
            $query .= " WHERE common_name LIKE :search ORDER BY element.common_name LIMIT " . self::MAX_RESULT;
        } else {
            $query .= " ORDER BY element.common_name LIMIT " . self::MAX_RESULT;
        }

        $statement = $this->pdo->prepare($query);

        if ($search) {
            $statement->bindValue('search', '%' . $search . '%');
        }

        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Get one row from database by ID.
     *
     * @param  int $id
     *
     * @return array
     */
    public function selectOneById(int $id)
    {
        $query = "SELECT " . self::TABLE . ".*, toxicity.name toxicity_name, element_type.name type_name
                    FROM " . self::TABLE . "
                    LEFT JOIN toxicity ON toxicity.id=element.toxicity_id
                    LEFT JOIN element_type ON element_type.id=element.element_type_id
                    WHERE element.id=:id";

        $statement = $this->pdo->prepare($query);
        $statement->bindValue('id', $id, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetch();
    }

    /**
     * Randomly retrieve a line
     *
     * @return array
     */
    public function selectOneAtRandom(): array
    {
        $query = "SELECT " . self::TABLE . ".*, toxicity.name toxicity_name, element_type.name type_name
                    FROM " . self::TABLE . "
                    LEFT JOIN toxicity ON toxicity.id=element.toxicity_id
                    LEFT JOIN element_type ON element_type.id=element.element_type_id
                    ORDER BY RAND() LIMIT 1";

        return $this->pdo->query($query)->fetch();
    }

    /**
     * Add an entry in the catalog table
     *
     * @param array $element
     * @return int
     */
    public function insert(array $element)
    {
        $query = "INSERT INTO " . self::TABLE . " 
            (`common_name`, `latin_name`, `color`, `picture`, `description`, `element_type_id`, `toxicity_id`)
            VALUES (:common_name, :latin_name, :color, :picture, :description, :element_type_id, :toxicity_id)";

        $statement = $this->pdo->prepare($query);
        $statement->bindValue('common_name', $element['commonName'], \PDO::PARAM_STR);
        $statement->bindValue('latin_name', $element['latinName'], \PDO::PARAM_STR);
        $statement->bindValue('color', $element['color'], \PDO::PARAM_STR);
        $statement->bindValue('picture', $element['picture'], \PDO::PARAM_STR);
        $statement->bindValue('description', $element['description'], \PDO::PARAM_STR);
        $statement->bindValue('element_type_id', $element['type'], \PDO::PARAM_INT);
        $statement->bindValue('toxicity_id', $element['toxicity'], \PDO::PARAM_INT);

        if ($statement->execute()) {
            return (int)$this->pdo->lastInsertId();
        }
    }

    /**
     * Update an entry in the catalog table
     *
     * @param array $element
     * @return void
     */
    public function update(array $element)
    {
        $query = "UPDATE " . self::TABLE . " SET `common_name` = :common_name, `latin_name` = :latin_name, 
        `color` = :color, `picture` = :picture, `description` = :description, `element_type_id` = :element_type_id, 
        `toxicity_id` = :toxicity_id WHERE id = :id";

        $statement = $this->pdo->prepare($query);
        $statement->bindValue('id', $element['id'], \PDO::PARAM_STR);
        $statement->bindValue('common_name', $element['commonName'], \PDO::PARAM_STR);
        $statement->bindValue('latin_name', $element['latinName'], \PDO::PARAM_STR);
        $statement->bindValue('color', $element['color'], \PDO::PARAM_STR);
        $statement->bindValue('picture', $element['picture'], \PDO::PARAM_STR);
        $statement->bindValue('description', $element['description'], \PDO::PARAM_STR);
        $statement->bindValue('element_type_id', $element['type'], \PDO::PARAM_INT);
        $statement->bindValue('toxicity_id', $element['toxicity'], \PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * Delete an entry in the catalog tables
     *
     * @param int $id
     */
    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare("DELETE FROM " . self::TABLE . " WHERE id=:id");
        $statement->bindValue('id', $id, \PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * Retrieve the number of records in the table
     *
     * @return int
     */
    public function getNumberCatalogElement(): int
    {
        $query = 'SELECT id FROM ' . self::TABLE;
        $statement = $this->pdo->query($query);

        return $statement->rowCount();
    }

    /**
     * Retrieve the number of search results
     *
     * @param string $search
     * @return int
     */
    public function getNumberSearchResult(string $search): int
    {
        $query = "SELECT " . self::TABLE . ".*, toxicity.name toxicity_name, element_type.name type_name
                    FROM " . self::TABLE . "
                    LEFT JOIN toxicity ON toxicity.id=element.toxicity_id
                    LEFT JOIN element_type ON element_type.id=element.element_type_id
                    WHERE common_name LIKE :search ORDER BY element.common_name";

        $statement = $this->pdo->prepare($query);
        $statement->bindValue('search', '%' . $search . '%');
        $statement->execute();

        return $statement->rowCount();
    }

    /**
     * Select an element group
     *
     * @param int $pageNumber
     * @param string $search
     * @return array
     */
    public function selectByPage(int $pageNumber, string $search = ''): array
    {
        if ($search) {
            $start = ($pageNumber - 1) * 5;
        } else {
            $start = ($pageNumber - 1) * self::MAX_RESULT;
        }

        $query = "SELECT " . self::TABLE . ".*, toxicity.name toxicity_name, element_type.name type_name
                    FROM " . self::TABLE . "
                    LEFT JOIN toxicity ON toxicity.id=element.toxicity_id
                    LEFT JOIN element_type ON element_type.id=element.element_type_id";

        if ($search) {
            $query .= " WHERE element.common_name LIKE :search 
            ORDER BY element.common_name LIMIT " . $start . ' OFFSET ' . self::MAX_RESULT;
        } else {
            $query .= " ORDER BY element.common_name LIMIT " . $start . ' OFFSET ' . self::MAX_RESULT;
        }

        $statement = $this->pdo->prepare($query);

        if ($search) {
            $statement->bindValue('search', '%' . $search . '%');
        }

        $statement->execute();

        return $statement->fetchAll();
    }
}
