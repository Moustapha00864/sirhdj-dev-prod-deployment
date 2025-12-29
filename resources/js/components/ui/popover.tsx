"use client"

import * as React from "react"
import { cn } from "@/lib/utils"

interface PopoverContextType {
  open: boolean
  setOpen: (open: boolean) => void
}

const PopoverContext = React.createContext<PopoverContextType | null>(null)

function usePopover() {
  const context = React.useContext(PopoverContext)
  if (!context) {
    throw new Error("Popover components must be used within a Popover")
  }
  return context
}

interface PopoverProps {
  children: React.ReactNode
  open?: boolean
  onOpenChange?: (open: boolean) => void
}

const Popover = ({ children, open: controlledOpen, onOpenChange }: PopoverProps) => {
  const [uncontrolledOpen, setUncontrolledOpen] = React.useState(false)

  const open = controlledOpen !== undefined ? controlledOpen : uncontrolledOpen
  const setOpen = React.useCallback((val: boolean) => {
    if (onOpenChange) {
      onOpenChange(val)
    } else {
      setUncontrolledOpen(val)
    }
  }, [onOpenChange])

  return (
    <PopoverContext.Provider value={{ open, setOpen }}>
      <div className="relative w-full">{children}</div>
    </PopoverContext.Provider>
  )
}

const PopoverTrigger = ({ children, asChild }: { children: React.ReactElement, asChild?: boolean }) => {
  const { open, setOpen } = usePopover()

  return React.cloneElement(children, {
    onClick: (e: React.MouseEvent) => {
      e.preventDefault()
      setOpen(!open)
      if (children.props.onClick) children.props.onClick(e)
    }
  })
}

const PopoverContent = React.forwardRef<
  HTMLDivElement,
  React.HTMLAttributes<HTMLDivElement> & { align?: string, sideOffset?: number }
>(({ className, align = "center", sideOffset = 4, ...props }, ref) => {
  const { open, setOpen } = usePopover()
  const contentRef = React.useRef<HTMLDivElement>(null)

  // Close on click outside
  React.useEffect(() => {
    if (!open) return

    const handleClickOutside = (event: MouseEvent) => {
      if (contentRef.current && !contentRef.current.contains(event.target as Node)) {
        setOpen(false)
      }
    }

    document.addEventListener("mousedown", handleClickOutside)
    return () => document.removeEventListener("mousedown", handleClickOutside)
  }, [open, setOpen])

  if (!open) return null

  return (
    <div
      ref={(node) => {
        // Handle both refs
        if (typeof ref === "function") ref(node)
        else if (ref) ref.current = node
        // @ts-ignore
        contentRef.current = node
      }}
      className={cn(
        "absolute z-[100] mt-1 w-full rounded-md border bg-white p-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none",
        className
      )}
      {...props}
    />
  )
})
PopoverContent.displayName = "PopoverContent"

export { Popover, PopoverTrigger, PopoverContent }