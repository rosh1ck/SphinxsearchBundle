<?php

namespace IAkumaI\SphinxsearchBundle\Search;

use IAkumaI\SphinxsearchBundle\Exception\EmptyIndexException;
use IAkumaI\SphinxsearchBundle\Exception\NoSphinxAPIException;

use SphinxClient;


class Sphinxsearch
{
    /**
     * @var string $host
     */
    private $host;

    /**
     * @var string $port
     */
    private $port;

    /**
     * @var string $socket
     */
    private $socket;

    /**
     * @var SphinxClient $sphinx
     */
    private $sphinx;

    /**
     * Constructor
     *
     * @param string $host Sphinx host
     * @param string $port Sphinx port
     * @param string $socket UNIX socket. Not required
     *
     * @throws NoSphinxAPIException If class SphinxClient does not exists
     */
    public function __construct($host, $port, $socket = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->socket = $socket;

        if (!class_exists('SphinxClient')) {
            throw new NoSphinxAPIException('You should include PHP SphinxAPI');
        }

        $this->sphinx = new SphinxClient();

        if (!is_null($this->socket)) {
            $this->sphinx->setServer($this->socket);
        } else {
            $this->sphinx->setServer($this->host, $this->port);
        }

        if (!defined('SEARCHD_OK')) {
            // To prevent notice
            define('SEARCHD_OK', 0);
        }
    }

    /**
     * Search for a query string
     * @param  string  $query   Search query
     * @param  array   $indexes Index list to perform the search
     * @param  boolean $escape  Should the query to be escaped?
     *
     * @return array           Search results
     *
     * @throws EmptyIndexException If $indexes is empty
     * @throws RuntimeException If seaarch failed
     */
    public function search($query, array $indexes, $escape = true)
    {
        if (empty($indexes)) {
            throw new EmptyIndexException('Try to search with empty indexes');
        }

        if ($escape) {
            $query = $this->sphinx->escapeString($query);
        }

        $qIndex = implode(' ', $indexes);

        $results = $this->sphinx->query($query, $indexNames);

        if (!is_array($results) || !isset($results['status']) || $results['status'] !== SEARCHD_OK) {
            throw new \RuntimeException(sprintf('Searching for "%s" failed with error "%s"', $query, $this->sphinx->getLastError()));
        }

        return $results;
    }

    /**
     * Adds a query to a multi-query batch
     *
     * @param string $query Search query
     * @param array $indexes Index list to perform the search
     *
     * @throws EmptyIndexException If $indexes is empty
     */
    public function addQuery($query, array $indexes)
    {
        if (empty($indexes)) {
            throw new EmptyIndexException('Try to search with empty indexes');
        }

        $this->sphinx->addQuery($query, implode(' ', $indexes));
    }

    /**
     * Magic PHP-proxy
     */
    public function __call($method, $args)
    {
        if (is_callable(array($this->sphinx, $method))) {
            return call_user_func_array(array($this->sphinx, $method), $args);
        } else {
            trigger_error("Call to undefined method '{$method}'");
        }
    }
}
