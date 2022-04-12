<?php

namespace App\Http\Livewire\Data;

use App\Models\Generic\Block;
use App\Models\Generic\Mapping;
use Livewire\Component;

class MappingEditor extends Component
{
    public $mappingId = null;

    public $articleAdd = "";
    public $codeText = "";

    public function removeArticle($art) {
        $mapping = Mapping::find($this->mappingId);

        $articles = array_map('trim', array_filter(explode(',', $mapping->articles), function($value) {
            return $value != '';
        }));

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

        $articles = array_map('trim', array_filter(explode(',', $mapping->articles), function($value) {
            return $value != '';
        }));

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

    public function removeBlock($key) {
        $mapping = Mapping::find($this->mappingId);
        $blocks = $mapping->blocks;
        unset($blocks[$key]);
        $blocks = array_values($blocks);

        $mapping->update([
            'blocks' => $blocks
        ]);
    }

    public function updateBlocks() {
        $mapping = Mapping::find($this->mappingId);

        $decodedText = json_decode($this->codeText);

        if($decodedText == null) {
            $this->addError('json', 'Der Json Block hat einen Fehler!');
            return false;
        }

        $mapping->update([
            'blocks' => $decodedText
        ]);

        session()->flash('success');
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

        $this->codeText = json_encode($mapping->blocks, JSON_PRETTY_PRINT);

        $articles = collect(array_map('trim', array_filter(explode(',', $mapping->articles), function($value) {
            return $value != '';
        })));

        return view('livewire.data.mapping-editor', compact('blocks', 'articles', 'mapping'));
    }
}
