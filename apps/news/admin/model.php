<?php

/**
 * Wity CMS
 * Système de gestion de contenu pour tous.
 *
 * @author Fofif
 * @version	$Id: apps/news/admin/model.php 0003 01-08-2011 Fofif $
 */
class NewsAdminModel {

        private $db;

        public function __construct() {
                $this->db = WSystem::getDB();
        }

        /**
         * Récupère la liste complète des pages
         */
        public function getNewsList($from, $number, $data_model, $order = 'title', $asc = true, $data_cats_model = array()) {
                $prep = $this->db->prepare('
			SELECT id, url, title, author, content, DATE_FORMAT(date, "%d/%m/%Y %H:%i") AS date, 
				DATE_FORMAT(modified, "%d/%m/%Y %H:%i") AS modified, views, editor_id
			FROM news
			ORDER BY news.' . $order . ' ' . ($asc ? 'ASC' : 'DESC') . '
			LIMIT :start, :number
		');
                $prep->bindParam(':start', $from, PDO::PARAM_INT);
                $prep->bindParam(':number', $number, PDO::PARAM_INT);
                $prep->execute();
                $result = $prep->fetchAll();

                foreach ($result as $key => $news) {
                        
                        $result[$key][$data_model['fromDB']['cat']] = $this->findNewsCats($news['id'],$data_cats_model);
                        
                        foreach ($news as $prop => $value) {
                                if (!empty($data_model['fromDB'][$prop])) {
                                        unset($result[$key][$prop]);
                                        $result[$key][$data_model['fromDB'][$prop]] = $value;
                                }
                        }
                }
                return $result;
        }

        /**
         * Récupère les catégories liées à une news
         */
        public function findNewsCats($nid, $data_model = array()) {
                $prep = $this->db->prepare('
			SELECT cat_id, name
			FROM news_cats_relations
			LEFT JOIN news_cats
			ON cat_id = cid
			WHERE news_id = :nid
		');
                $prep->bindParam(':nid', $nid, PDO::PARAM_INT);
                $prep->execute();
                $result = $prep->fetchAll();

                foreach ($result as $key => $cat) {                        
                        foreach ($cat as $prop => $value) {
                                if (!empty($data_model['fromDB'][$prop])) {
                                        unset($result[$key][$prop]);
                                        $result[$key][$data_model['fromDB'][$prop]] = $value;
                                }
                        }
                }
                return $result;
        }

        /**
         * Création d'une news
         */
        public function createNews($data, $data_model) {
                $prep = $this->db->prepare('
			INSERT INTO news(url, title, author, content, keywords, date, image)
			VALUES (:url, :title, :author, :content, :keywords, NOW(), :image)
		');
                $prep->bindParam(':url', $data[$data_model['fromDB']['url']]);
                $prep->bindParam(':title', $data[$data_model['fromDB']['title']]);
                $prep->bindParam(':author', $data[$data_model['fromDB']['author']]);
                $prep->bindParam(':content', $data[$data_model['fromDB']['content']]);
                $prep->bindParam(':keywords', $data[$data_model['fromDB']['keywords']]);
                $prep->bindParam(':image', $data[$data_model['fromDB']['image']]);
                return $prep->execute();
        }

        /**
         * Obtenir le dernier id inséré dans la table
         * Utile pour le nouveau routage lors de la création d'une page
         */
        public function getLastNewsId() {
                $prep = $this->db->prepare('
			SELECT id FROM news ORDER BY id DESC LIMIT 1
		');
                $prep->execute();
                return intval($prep->fetchColumn());
        }

        /**
         * Récupération des données d'une news
         */
        public function loadNews($id, $data_model = array()) {
                $prep = $this->db->prepare('
			SELECT url, title, author, content, keywords, DATE_FORMAT(date, "%d/%m/%Y %H:%i") AS date, DATE_FORMAT(modified, "%d/%m/%Y %H:%i") AS modified, image
			FROM news
			WHERE id = :id
		');
                $prep->bindParam(':id', $id, PDO::PARAM_INT);
                $prep->execute();
                $result = $prep->fetch();

                foreach ($result as $key => $val) {
                        if (!empty($data_model['fromDB'][$key])) {
                                unset($result[$key]);
                                $result[$data_model['fromDB'][$key]] = $val;
                        }
                }

                return $result;
        }

        /**
         * Mise à jour d'une news
         */
        public function updateNews($data, $data_model) {
                $prep = $this->db->prepare('
                        UPDATE news
                        SET url = :url, title = :title, content = :content, keywords = :keywords, modified = NOW(), editor_id = :editor_id, image = :image 
			WHERE id = :id
		');
                $prep->bindParam(':id', $data[$data_model['fromDB']['id']]);
                $prep->bindParam(':url', $data[$data_model['fromDB']['url']]);
                $prep->bindParam(':title', $data[$data_model['fromDB']['title']]);
                $prep->bindParam(':content', $data[$data_model['fromDB']['content']]);
                $prep->bindParam(':keywords', $data[$data_model['fromDB']['keywords']]);
                $prep->bindParam(':editor_id', $_SESSION[$data_model['fromDB']['editor_id']]);
                $prep->bindParam(':image', $data[$data_model['fromDB']['image']]);
                return $prep->execute();
        }

        /**
         * Suppression d'une news
         */
        public function deleteNews($id) {
                $prep = $this->db->prepare('
			DELETE FROM news WHERE id = :id
		');
                $prep->bindParam(':id', $id, PDO::PARAM_INT);
                return $prep->execute();
        }

        /**
         * Vérifie l'existence d'une news pour un id donné
         */
        public function validExistingNewsId($id) {
                if(empty($id)) {
                        return false;
                }
                
                $prep = $this->db->prepare('
			SELECT * FROM news WHERE id = :id
		');
                $prep->bindParam(':id', $id, PDO::PARAM_INT);
                $prep->execute();
                return $prep->rowCount() == 1;
        }

        /**
         * Vérifie l'existence d'une catégorie pour un id donné
         */
        public function validExistingCatId($cid) {
                if(empty($cid)) {
                        return false;
                }
                
                $prep = $this->db->prepare('
			SELECT * FROM news_cats WHERE cid = :cid
		');
                $prep->bindParam(':cid', $cid, PDO::PARAM_INT);
                $prep->execute();
                return $prep->rowCount() == 1;
        }

        /**
         * Ajout d'une relation news/cat
         */
        public function newsAddCat($nid, $cid) {
                $prep = $this->db->prepare('
			INSERT INTO news_cats_relations(news_id, cat_id)
			VALUES (:nid, :cid)
		');
                $prep->bindParam(':nid', $nid, PDO::PARAM_INT);
                $prep->bindParam(':cid', $cid, PDO::PARAM_INT);
                return $prep->execute();
        }

        /**
         * Destruction des relations news/cats d'une news
         */
        public function newsDestroyCats($nid) {
                $prep = $this->db->prepare('
			DELETE FROM news_cats_relations WHERE news_id = :nid
		');
                $prep->bindParam(':nid', $nid, PDO::PARAM_INT);
                return $prep->execute();
        }

        /**
         * Destruction des relations news/cats d'une catégorie
         */
        public function catsDestroyNews($cid) {
                $prep = $this->db->prepare('
			DELETE FROM news_cats_relations WHERE cat_id = :cid
		');
                $prep->bindParam(':cid', $cid, PDO::PARAM_INT);
                return $prep->execute();
        }

        /**
         * Récupère la liste complète des catégories
         */
        public function getCatList($data_model = array(), $order = 'name', $asc = true) {
                $prep = $this->db->prepare('
			SELECT cid, name, shortname, parent
			FROM news_cats
			ORDER BY ' . $order . ' ' . ($asc ? 'ASC' : 'DESC')
                );
                $prep->execute();                
                $result = $prep->fetchAll(PDO::FETCH_ASSOC);

                foreach ($result as $key => $cat) {
                        foreach ($cat as $prop => $value) {
                                if (!empty($data_model['fromDB'][$prop])) {
                                        unset($result[$key][$prop]);
                                        $result[$key][$data_model['fromDB'][$prop]] = $value;
                                }
                        }
                }
                return $result;
        }

        /**
         * Vérifie l'existence d'une catégorie
         */
        public function catExists($id) {
                $prep = $this->db->prepare('
			SELECT * FROM news_cats WHERE cid = :id
		');
                $prep->bindParam(':id', $id, PDO::PARAM_INT);
                $prep->execute();
                return $prep->rowCount() == 1;
        }

        /**
         * Création d'une catégorie
         */
        public function createCat($data, $data_model) {
                $prep = $this->db->prepare('
			INSERT INTO news_cats(name, shortname, parent)
			VALUES (:name, :shortname, :parent)
		');
                $prep->bindParam(':name', $data[$data_model['fromDB']['name']]);
                $prep->bindParam(':shortname', $data[$data_model['fromDB']['shortname']]);
                $prep->bindParam(':parent', $data[$data_model['fromDB']['parent']]);
                return $prep->execute();
        }

        /**
         * Mise à jour d'une catégorie
         */
        public function updateCat($data, $data_model) {                
                $prep = $this->db->prepare('
			UPDATE news_cats
                        SET name = :name, shortname = :shortname, parent = :parent
			WHERE cid = :cid
		');
                $prep->bindParam(':name', $data[$data_model['fromDB']['name']]);
                $prep->bindParam(':shortname', $data[$data_model['fromDB']['shortname']]);
                $prep->bindParam(':parent', $data[$data_model['fromDB']['parent']]);
                $prep->bindParam(':cid', $data[$data_model['fromDB']['cid']]);
                return $prep->execute();
        }

        /**
         * Suppression d'une catégorie
         */
        public function deleteCat($id) {
                $prep = $this->db->prepare('
			DELETE FROM news_cats WHERE cid = :id
		');
                $prep->bindParam(':id', $id, PDO::PARAM_INT);
                return $prep->execute();
        }

}

?>
