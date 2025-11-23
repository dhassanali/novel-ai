import { Switch as HeadlessSwitch } from '@headlessui/react'
import { cn } from "@/lib/utils"

function Switch({
    checked,
    onCheckedChange,
    className,
    id
}: {
    checked: boolean,
    onCheckedChange: (checked: boolean) => void,
    className?: string,
    id?: string
}) {
    return (
        <HeadlessSwitch
            checked={checked}
            onChange={onCheckedChange}
            id={id}
            className={cn(
                "peer inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50 data-[checked]:bg-primary data-[checked]:text-primary-foreground data-[checked]:hover:bg-primary/90 bg-input",
                className
            )}
        >
            <span className="pointer-events-none block h-5 w-5 rounded-full bg-background shadow-lg ring-0 transition-transform data-[checked]:translate-x-5 data-[unchecked]:translate-x-0" />
        </HeadlessSwitch>
    )
}

export { Switch }
