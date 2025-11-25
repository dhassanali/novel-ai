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
import { Character } from '@/types/novel';
import { useEffect, useState } from 'react';

interface CharacterModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    character: Character | null;
    onSave: (character: Partial<Character>) => void;
}

export default function CharacterModal({
    open,
    onOpenChange,
    character,
    onSave,
}: CharacterModalProps) {
    const [name, setName] = useState('');
    const [role, setRole] = useState('');
    const [description, setDescription] = useState('');

    useEffect(() => {
        if (character) {
            setName(character.name);
            setRole(character.role);
            setDescription(character.description || '');
        } else {
            setName('');
            setRole('');
            setDescription('');
        }
    }, [character, open]);

    const handleSave = () => {
        onSave({
            name,
            role,
            description,
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {character ? 'Edit Character' : 'Add Character'}
                    </DialogTitle>
                </DialogHeader>
                <div className="space-y-4">
                    <div>
                        <Label htmlFor="char-name">Name</Label>
                        <Input
                            id="char-name"
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            placeholder="Character Name"
                        />
                    </div>
                    <div>
                        <Label htmlFor="char-role">Role</Label>
                        <Input
                            id="char-role"
                            value={role}
                            onChange={(e) => setRole(e.target.value)}
                            placeholder="Protagonist, Antagonist, etc."
                        />
                    </div>
                    <div>
                        <Label htmlFor="char-desc">Description</Label>
                        <Textarea
                            id="char-desc"
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                            placeholder="Physical appearance, personality, etc."
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button onClick={handleSave}>Save</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
