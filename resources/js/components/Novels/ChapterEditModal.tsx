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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Chapter, Character, ChapterStatus } from '@/types/novel';
import { useEffect, useState } from 'react';

interface ChapterEditModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    chapter: Chapter | null;
    characters: Character[];
    onSave: (data: {
        title: string;
        status: ChapterStatus;
        pov_character_id: number | null;
    }) => void;
}

const STATUS_LABELS: Record<ChapterStatus, string> = {
    draft: 'Draft',
    writing: 'Writing',
    complete: 'Complete',
};

export default function ChapterEditModal({
    open,
    onOpenChange,
    chapter,
    characters,
    onSave,
}: ChapterEditModalProps) {
    const [title, setTitle] = useState('');
    const [status, setStatus] = useState<ChapterStatus>('draft');
    const [povCharacterId, setPovCharacterId] = useState<number | null>(null);

    useEffect(() => {
        if (chapter) {
            setTitle(chapter.title);
            setStatus(chapter.status ?? 'draft');
            setPovCharacterId(chapter.pov_character_id);
        }
    }, [chapter, open]);

    const handleSave = () => {
        onSave({ title, status, pov_character_id: povCharacterId });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit Chapter</DialogTitle>
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
                    <div>
                        <Label htmlFor="chapter-status">Status</Label>
                        <Select
                            value={status}
                            onValueChange={(v) =>
                                setStatus(v as ChapterStatus)
                            }
                        >
                            <SelectTrigger id="chapter-status">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {(
                                    Object.keys(
                                        STATUS_LABELS,
                                    ) as ChapterStatus[]
                                ).map((s) => (
                                    <SelectItem key={s} value={s}>
                                        {STATUS_LABELS[s]}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    {characters.length > 0 && (
                        <div>
                            <Label htmlFor="chapter-pov">
                                POV Character
                            </Label>
                            <Select
                                value={
                                    povCharacterId !== null
                                        ? String(povCharacterId)
                                        : 'none'
                                }
                                onValueChange={(v) =>
                                    setPovCharacterId(
                                        v === 'none' ? null : Number(v),
                                    )
                                }
                            >
                                <SelectTrigger id="chapter-pov">
                                    <SelectValue placeholder="None" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        None
                                    </SelectItem>
                                    {characters.map((c) => (
                                        <SelectItem
                                            key={c.id}
                                            value={String(c.id)}
                                        >
                                            {c.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}
                </div>
                <DialogFooter>
                    <Button onClick={handleSave}>Save Changes</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
