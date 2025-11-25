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
import { Chapter } from '@/types/novel';
import { useEffect, useState } from 'react';

interface ChapterEditModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    chapter: Chapter | null;
    onSave: (title: string) => void;
}

export default function ChapterEditModal({
    open,
    onOpenChange,
    chapter,
    onSave,
}: ChapterEditModalProps) {
    const [title, setTitle] = useState('');

    useEffect(() => {
        if (chapter) {
            setTitle(chapter.title);
        }
    }, [chapter, open]);

    const handleSave = () => {
        onSave(title);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit Chapter Title</DialogTitle>
                </DialogHeader>
                <div className="space-y-4">
                    <div>
                        <Label htmlFor="chapter-title">Title</Label>
                        <Input
                            id="chapter-title"
                            value={title}
                            onChange={(e) => setTitle(e.target.value)}
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
