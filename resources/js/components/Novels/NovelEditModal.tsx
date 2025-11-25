import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Novel } from '@/types/novel';
import { useEffect, useState } from 'react';

interface NovelEditModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    novel: Novel;
    onSave: (data: {
        title: string;
        genre: string;
        description: string;
    }) => void;
}

export default function NovelEditModal({
    open,
    onOpenChange,
    novel,
    onSave,
}: NovelEditModalProps) {
    const [title, setTitle] = useState(novel.title);
    const [genre, setGenre] = useState(novel.genre || '');
    const [description, setDescription] = useState(novel.description || '');

    useEffect(() => {
        setTitle(novel.title);
        setGenre(novel.genre || '');
        setDescription(novel.description || '');
    }, [novel, open]);

    const handleSave = () => {
        onSave({
            title,
            genre,
            description,
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit Novel Details</DialogTitle>
                </DialogHeader>
                <div className="space-y-4">
                    <div>
                        <Label htmlFor="novel-title">Title</Label>
                        <Input
                            id="novel-title"
                            value={title}
                            onChange={(e) => setTitle(e.target.value)}
                        />
                    </div>
                    <div>
                        <Label htmlFor="novel-genre">Genre</Label>
                        <Input
                            id="novel-genre"
                            value={genre}
                            onChange={(e) => setGenre(e.target.value)}
                            placeholder="Fantasy, Sci-Fi, etc."
                        />
                    </div>
                    <div>
                        <Label htmlFor="novel-description">Description</Label>
                        <Textarea
                            id="novel-description"
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                            placeholder="A brief summary of your novel..."
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button onClick={handleSave}>Save Changes</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
