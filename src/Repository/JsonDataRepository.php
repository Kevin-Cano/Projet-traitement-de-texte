<?php

namespace App\Repository;

/**
 * Repository simple utilisant des fichiers JSON
 * Solution temporaire pour éviter les problèmes de base de données
 */
class JsonDataRepository
{
    private string $dataDir;

    public function __construct()
    {
        $this->dataDir = __DIR__ . '/../../var/data';
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }

    public function findAll(string $entity): array
    {
        $file = $this->dataDir . '/' . strtolower($entity) . 's.json';
        if (!file_exists($file)) {
            return [];
        }
        
        $data = json_decode(file_get_contents($file), true);
        return $data ?: [];
    }

    public function find(string $entity, int $id): ?array
    {
        $items = $this->findAll($entity);
        foreach ($items as $item) {
            if ($item['id'] == $id) {
                return $item;
            }
        }
        return null;
    }

    public function save(string $entity, array $data): array
    {
        $items = $this->findAll($entity);
        
        if (!isset($data['id'])) {
            // Nouveau item
            $maxId = 0;
            foreach ($items as $item) {
                if ($item['id'] > $maxId) {
                    $maxId = $item['id'];
                }
            }
            $data['id'] = $maxId + 1;
            $data['dateCreation'] = date('Y-m-d H:i:s');
        } else {
            // Mise à jour
            $data['dateModification'] = date('Y-m-d H:i:s');
            // Remplacer l'item existant
            foreach ($items as $index => $item) {
                if ($item['id'] == $data['id']) {
                    $items[$index] = $data;
                    $this->saveToFile($entity, $items);
                    return $data;
                }
            }
        }
        
        $items[] = $data;
        $this->saveToFile($entity, $items);
        return $data;
    }

    public function delete(string $entity, int $id): bool
    {
        $items = $this->findAll($entity);
        foreach ($items as $index => $item) {
            if ($item['id'] == $id) {
                unset($items[$index]);
                $this->saveToFile($entity, array_values($items));
                return true;
            }
        }
        return false;
    }

    private function saveToFile(string $entity, array $data): void
    {
        $file = $this->dataDir . '/' . strtolower($entity) . 's.json';
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function findBy(string $entity, array $criteria): array
    {
        $items = $this->findAll($entity);
        $result = [];
        
        foreach ($items as $item) {
            $match = true;
            foreach ($criteria as $key => $value) {
                if (!isset($item[$key]) || $item[$key] != $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                $result[] = $item;
            }
        }
        
        return $result;
    }

    /**
     * Alias pour findAll() - pour la compatibilité avec les controllers
     */
    public function findAllByType(string $type): array
    {
        return $this->findAll($type);
    }

    /**
     * Alias pour find() - pour la compatibilité avec les controllers
     */
    public function findById(string $entity, int $id): ?array
    {
        return $this->find($entity, $id);
    }
} 