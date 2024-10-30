<?php

namespace Kanboard\Plugin\Wiki\Helper;

use Kanboard\Core\Base;
use Kanboard\Model\Wiki;


class WikiHelper extends Base
{
    /**
     * SQL table name
     *
     * @var string
     */
    const WIKITABLE = 'wikipage';

    /**
     * Get all Wiki Pages by order for a project
     *
     * @access public
     * @param  integer   $project_id
     * @return array
     */
    public function getWikipages($project_id)
    {
        return null;
        // return wiki::getWikipages($project['id']);
        // return $this->wikiModel->getWikipages($project['id']);
        // return $this->db->table(self::WIKITABLE)->eq('project_id', $project_id)->desc('order')->findAll();
    }

    /**
     * Add a Javascript asset
     *
     * @param  string $filepath Filepath
     * @param  bool   $async
     * @return string
     */
    public function js($filepath, $async = false)
    {
        return '<script '.($async ? 'async' : '').' defer type="text/javascript" src="'.$this->helper->url->dir().$filepath.'?'.filemtime($filepath).'"></script>';
    }

    /**
     * render wiki page html children recursively
     * @param mixed $children
     * @param mixed $parent_id
     * @param mixed $project
     * @param mixed $selected_wiki_id
     * @param mixed $not_editable
     * @return string
     */
    public function renderChildren($children, $parent_id, $project, $selected_wiki_id, $not_editable) {
        $html = '<ul data-parent-id="'.$parent_id.'">';
        foreach ($children as $item) {
            $is_active = ($selected_wiki_id == $item['id']) ? ' active' : '';
            $has_children = isset($item['children']) && (count($item['children']) > 0);
            $html .= '<li class="wikipage'.$is_active.'" data-project-id="'.$project['id'].'" data-page-id="'.$item['id'].'" data-page-order="'.$item['ordercolumn'].'">';
            if(!$not_editable) {
                $html .= '<div style="float: right">';
                $html .= '<button class="action">';
                $html .= $this->helper->modal->medium('edit', '', 'WikiController', 'edit', array('plugin' => 'wiki', 'wiki_id' => $item['id']));
                $html .= '</button>';
                $html .= '<button class="action">';
                $html .= $this->helper->modal->confirm('trash-o', '', 'WikiController', 'confirm', array('plugin' => 'wiki', 'project_id' => $project['id'], 'wiki_id' => $item['id']));
                $html .= '</button>';
                $html .= '</div>';
            }
            if($has_children){
                $html .= '<button class="branch"><a><i class="fa fa-minus-square-o"></i></a></button>';
                $wikipage_icon = 'folder-o';
            } else {
                $html .= '<button class="indent"><i class="fa fa-square-o"></i></button>';
                $wikipage_icon = 'file-word-o';
            }
            $html .= '<button class="indent"></button>';
            if(!$not_editable) {
                $html .= $this->helper->url->icon(
                    $wikipage_icon, t($item['title']), 'WikiController', 'detail', array('plugin' => 'wiki', 'project_id' => $project['id'], 'wiki_id' => $item['id']), false, 'wikilink'.$is_active
                );
            } else {
                $html .= $this->helper->url->icon(
                    $wikipage_icon, t($item['title']), 'WikiController', 'detail_readonly', array('plugin' => 'wiki', 'token' => $project['token'], 'wiki_id' => $item['id']), false, 'wikilink'.$is_active
                );
            }
            if($has_children) {
                $html .= $this->renderChildren($item['children'], $item['id'], $project, $selected_wiki_id, $not_editable);
            }
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    /**
     * generate indented sublist of children
     * @param mixed $children
     * @param mixed $parent_id
     */
    public function generateIndentedChildren($children, $use_full_pages = false, $parent_id = 0, $exclude_wiki_id = 0, $indent = 0) {
        $indentedChildren = array();
        if ($parent_id == 0) {
            $indentedChildren[''] = t('(root)');
        }
        foreach ($children as $item) {
            if ($exclude_wiki_id != $item['id']) {
                if ($use_full_pages) {
                    $item['title'] = str_repeat('&nbsp;', ($indent + 1) * 4) . ' ' . $item['title'];
                    $indentedChildren[$item['id']] = $item;
                } else {
                    $indentedChildren[$item['id']] = str_repeat('&nbsp;', ($indent + 1) * 4) . ' ' . $item['title'];
                }
                if (count($item['children']) > 0) {
                    $nestedChildren = $this->generateIndentedChildren($item['children'], $use_full_pages, $item['id'], $exclude_wiki_id, $indent + 1);
                    $indentedChildren += $nestedChildren;
                }
            }
        }
        return $indentedChildren;
    }
}
