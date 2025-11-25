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
import { Location } from '@/types/novel';
import { useEffect, useState } from 'react';

interface LocationModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    location: Location | null;
    onSave: (location: Partial<Location>) => void;
}

export default function LocationModal({
    open,
    onOpenChange,
    location,
    onSave,
}: LocationModalProps) {
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');

    useEffect(() => {
        if (location) {
            setName(location.name);
            setDescription(location.description || '');
        } else {
            setName('');
            setDescription('');
        }
    }, [location, open]);

    const handleSave = () => {
        onSave({
            name,
            description,
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {location ? 'Edit Location' : 'Add Location'}
                    </DialogTitle>
                </DialogHeader>
                <div className="space-y-4">
                    <div>
                        <Label htmlFor="loc-name">Name</Label>
                        <Input
                            id="loc-name"
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            placeholder="Location Name"
                        />
                    </div>
                    <div>
                        <Label htmlFor="loc-desc">Description</Label>
                        <Textarea
                            id="loc-desc"
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                            placeholder="Geography, atmosphere, significance, etc."
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
