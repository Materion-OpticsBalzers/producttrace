<?php

namespace App\Http\Livewire\Data;

use App\Models\Generic\Block;
use App\Models\Generic\Mapping;
use Livewire\Component;

class MappingEditor extends Component
{
    public $mappingId = null;

    public $articleAdd = "";

    public function removeArticle($art) {
        $mapping = Mapping::find($this->mappingId);

        $articles = array_map('trim', explode(',', $mapping->articles));

        foreach(array_keys($articles, "'$art'", true) as $key) {
            unset($articles[$key]);
        }

        $mapping->update([
            'articles' => join(',', $articles)
        ]);
    }

    public function addArticle() {
        if($this->articleAdd == '') {
            $this->addError('article', 'Artikel darf nicht leer sein');
            return false;
        }

        $mapping = Mapping::find($this->mappingId);

        $articles = array_map('trim', explode(',', $mapping->articles));

        if(!in_array("'$this->articleAdd'", $articles)) {
            $articles[] = "'$this->articleAdd'";
        } else {
            $this->addError('article', 'Artikel schon vorhanden');
            return false;
        }

        $mapping->update([
            'articles' => join(',', $articles)
        ]);
    }

    public function render()
    {
        $mapping = Mapping::find($this->mappingId);

        $blocks = array();
        foreach($mapping->blocks as $block) {
            if(isset($block->type)) {
                $blocks[] = (object) $block;
            } else {
                $b = Block::find($block->id);
                $b->prev = $block->prev;
                $b->next = $block->next;
                $blocks[] = $b;
            }
        }

        $articles = collect(array_map('trim', explode(',', $mapping->articles)));

        return view('livewire.data.mapping-editor', compact('blocks', 'articles', 'mapping'));
    }
}
