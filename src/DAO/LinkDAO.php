<?php

namespace Watson\DAO;

use Watson\Domain\Link;

class LinkDAO extends DAO
{
    /**
     * @var \Watson\DAO\UserDAO
     */
    private $userDAO;

    /**
     * @var \Watson\DAO\TagDAO
     */
    private $tagDAO;

    public function setUserDAO($userDAO) {
        $this->userDAO = $userDAO;
    }

    public function setTagDAO($tagDAO) {
        $this->tagDAO = $tagDAO;
    }

    /**
     * Return a list of all links, sorted by date (most recent first).
     *
     * @return array A list of all links.
     */
    public function findAll() {
        $sql = "
            SELECT * 
            FROM tl_liens 
            ORDER BY lien_id DESC
        ";
        $result = $this->getDb()->fetchAll($sql);

        // Convert query result to an array of domain objects
        $_links = array();
        foreach ($result as $row) {
            $linkId          = $row['lien_id'];
            $_links[$linkId] = $this->buildDomainObject($row);
        }
        return $_links;
    }

    /**
     * Returns a link matching the supplied id.
     *
     * @param integer $id The link id.
     *
     * @return \Watson\Domain\Link|throws an exception if no matching link is found.
     */
    public function find($id) {
        $sql = "
            SELECT * 
            FROM tl_liens 
            WHERE lien_id = ?
        ";
        $row = $this->getDb()->fetchAssoc($sql, array($id));

        if ($row){
            return $this->buildDomainObject($row);
        }else{
            throw new \Exception("No link matching id " . $id);
        }
    }

    /**
     * Returns a list of all links matching the supplied tag id.
     *
     * @param integer $id The tag id.
     *
     * @return A list of all links.
     */
    public function findAllByTag($id) {
        $sql = "
            SELECT tl_liens.*
            FROM tl_liens
            INNER JOIN tl_tags_liens
                ON tl_tags_liens.lien_id = tl_liens.lien_id
            WHERE tl_tags_liens.tag_id = ?
        ";
        $result = $this->getDb()->fetchAll($sql, array($id));

        // Convert query result to an array of domain objects
        $_links = array();
        foreach ($result as $row) {
            $linkId          = $row['lien_id'];
            $_links[$linkId] = $this->buildDomainObject($row);
        }
        return $_links;
    }

    /**
     * Saves a link into the database.
     *
     * @param \Watson\Domain\Link $link The link to save
     */
    public function save(Link $link) {
        $linkData = array(
            'lien_titre' => $link->getTitle(),
            'lien_url'   => $link->getUrl(),
            'lien_desc'  => $link->getDesc(),
            'user_id'    => $link->getUser()->getId()
        );

        if ($link->getId()) {
            // The link has already been saved : update it
            $this->getDb()->update('tl_liens', $linkData, array('lien_id' => $link->getId()));
        } else {
            // The link has never been saved : insert it
            $this->getDb()->insert('tl_liens', $linkData);

            // Get the id of the newly created link and set it on the entity.
            $id = $this->getDb()->lastInsertId();
            $link->setId($id);
        }
    }

    /**
     * Removes an link from the database.
     *
     * @param integer $id The link id.
     */
    public function delete($id) {
        // Delete the link
        $this->getDb()->delete('tl_liens', array('lien_id' => $id));
    }

    /**
     * Creates an Link object based on a DB row.
     *
     * @param array $row The DB row containing Link data.
     * @return array $_tag With Objects of \Watson\Domain\Link
     */
    protected function buildDomainObject($row) {
        $link = new Link();
        $link->setId($row['lien_id']);
        $link->setTitle($row['lien_titre']);
        $link->setUrl($row['lien_url']);
        $link->setDesc($row['lien_desc']);
        if (array_key_exists('user_id', $row)) {
            // Find and set the associated author
            $userId = $row['user_id'];
            $user   = $this->userDAO->find($userId);
            $link->setUser($user);
        }

        // Find and set associated tags
        $linkId = $row['lien_id'];
        $_tag   = $this->tagDAO->find($linkId);
        $link->setTags($_tag);

        return $link;
    }

    /**
     * Removes all links for a user
     *
     * @param integer $userId The id of the user
     */
    public function deleteAllByUser($userId) {
        $this->getDb()->delete('tl_liens', array('user_id' => $userId));
    }
}
