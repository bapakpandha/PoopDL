"use client"
import React from 'react'
import { LogoText, LogoImage } from './Logo';
import { navItems, NavItem } from './HeaderLinks';
import Link from 'next/link';
import { Sheet, SheetTrigger, SheetTitle, SheetHeader, SheetContent } from './MobileSheets';
import { Button } from './Button';
import { Menu } from 'lucide-react';
import { useIsMobile } from './Utils';
import { IoIosArrowDown } from "react-icons/io";
import { useAutoAnimate } from "@formkit/auto-animate/react";

const Header = () => {
    const isMobile = useIsMobile();
    const [open, setOpen] = React.useState(false);
    // const t = (key: string) => { const translations: Record<string, string> = {"links.features": "Features", "links.howItWorks": "How it works", "links.frequentlyAsked": "Frequently Asked Questions", }; return translations[key] || key;};
    const scrollUp = () => {window.scrollTo({ top: 0, behavior: "smooth" });};

    React.useEffect(() => {
        if (!isMobile && open) {
            setOpen(false);
        }
    }, [isMobile, open]);

    return (
        <header className='sticky top-0 z-50 w-full border-b bg-white backdrop-blur supports-[backdrop-filter]:bg-gray-50/60 dark:bg-gray-900/80'>
            <div className='container mx-auto flex h-16 items-center px-4'>
                <div role='button' onClick={scrollUp} className='flex cursor-pointer items-center gap-2'>
                    <LogoImage className="h-6 w-6 text-teal-500" />
                    <LogoText />
                </div>

                <nav className='ml-auto hidden items-center gap-6 md:flex'>
                    {navItems.map((d, i) => (
                        <div key={i} className="relative flex items-center gap-2 group py-3">
                            {d.children ? (
                                <div className="px-2 py-3 text-sm font-medium transition-colors relative hover:text-teal-500">
                                    <p className='flex cursor-pointer items-center gap-2'>
                                        <span>{d.label || "undefined"}</span>
                                        <IoIosArrowDown className="rotate-0 transition-all group-hover:rotate-180" />
                                    </p>
                                    <div className="absolute top-full left-0 mt-2 w-48 flex-col bg-white shadow-lg rounded-md hidden transition-all group-hover:flex py-3">
                                        {d.children.map((child, j) => (
                                            <Link
                                                key={j}
                                                href={child.link || "#"}
                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            >
                                                {child.label || "undefined"}
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            ) : (
                                <Link
                                    href={d.link || "#"}
                                    className="px-2 py-3 text-sm font-medium transition-colors relative hover:text-teal-500"
                                >
                                    <span>{d.label || "undefined"}</span>
                                </Link>
                            )}
                        </div>
                    ))}

                </nav>

                {/* Desktop Nav */}
                {/* <nav className="ml-auto hidden items-center gap-6 md:flex">
                    
                    <Link
                        href={headerLinks.features}
                        className="text-sm font-medium transition-colors hover:text-teal-500"
                    >
                        {t("links.features")}
                    </Link>
                    <Link
                        href={headerLinks.howItWorks}
                        className="text-sm font-medium transition-colors hover:text-teal-500"
                    >
                        {t("links.howItWorks")}
                    </Link>
                    <Link
                        href={headerLinks.frequentlyAsked}
                        className="text-sm font-medium transition-colors hover:text-teal-500"
                    >
                        {t("links.frequentlyAsked")}
                    </Link>
                </nav> */}

                {/* Mobile Nav */}
                <div className="ml-auto flex items-center md:hidden">
                    <Sheet open={open} onOpenChange={setOpen}>
                        <SheetTrigger asChild>
                            <Button variant="ghost" size="icon" className="h-9 w-9">
                                <Menu className="h-[1.2rem] w-[1.2rem]" />
                                <span className="sr-only">Toggle menu</span>
                            </Button>
                        </SheetTrigger>
                        <SheetContent
                            side="right"
                            className="w-[80%] bg-gradient-to-b from-white to-gray-100 pr-0 sm:w-[350px] dark:from-gray-800 dark:to-gray-900"
                        >
                            <SheetHeader className="border-b">
                                <SheetTitle>
                                    <div className="flex items-center gap-2">
                                        <LogoImage className="h-6 w-6 text-teal-500" />
                                        <LogoText />
                                    </div>
                                </SheetTitle>
                            </SheetHeader>
                            <div className="flex h-full flex-col">
                                <nav className="flex flex-col gap-4 px-4">

                                    {navItems.map((d, i) => (
                                        d.children && d.children.length > 0 ? (
                                            <SingleNavItem
                                                key={i}
                                                label={d.label}
                                                iconImage={d.iconImage}
                                                link={d.link}
                                            >
                                                {d.children}
                                            </SingleNavItem>
                                        ) : (
                                            <Link
                                                key={i}
                                                href={d.link || "#"}
                                                className="px-2 py-2 text-lg font-medium transition-colors hover:text-teal-500"
                                                onClick={() => setOpen(false)}
                                            >
                                                {d.label || "undefined"}
                                            </Link>
                                        )
                                    ))}
                                    {/* 
                                    <Link
                                        href={headerLinks.features}
                                        className="px-2 py-2 text-lg font-medium transition-colors hover:text-teal-500"
                                        onClick={() => setOpen(false)}
                                    >
                                        {t("links.features")}
                                    </Link>

                                    <Link
                                        href={headerLinks.howItWorks}
                                        className="px-2 py-2 text-lg font-medium transition-colors hover:text-teal-500"
                                        onClick={() => setOpen(false)}
                                    >
                                        {t("links.howItWorks")}
                                    </Link>
                                    <Link
                                        href={headerLinks.frequentlyAsked}
                                        className="px-2 py-2 text-lg font-medium transition-colors hover:text-teal-500"
                                        onClick={() => setOpen(false)}
                                    >
                                        {t("links.frequentlyAsked")}
                                    </Link> */}
                                </nav>
                            </div>
                            {/* <div className="border-border mt-auto border-t px-4 py-2">
                                <div className="flex flex-col gap-2">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-medium">
                                            {t("themeLabel")}
                                        </span>
                                        <ThemeToggleButton variant="outline" />
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-medium">
                                            {t("localeLabel")}
                                        </span>
                                        <LocaleDropdown variant="outline" />
                                    </div>
                                </div>
                            </div> */}
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </header>
    )
}


function SingleNavItem(d: NavItem) {
    const [animationParent] = useAutoAnimate();
    const [isItemOpen, setItem] = React.useState(false);

    function toggleItem() {
        return setItem(!isItemOpen);
    }

    return (
        <div
            ref={animationParent}
            className="relative transition-all "
        >
            <p className="flex cursor-pointer items-center gap-2 px-2 py-2 text-lg font-medium transition-colors hover:text-teal-500 "
                onClick={toggleItem}>
                <span>{d.label}</span>
                {d.children && (
                    // rotate-180
                    <IoIosArrowDown
                        className={`text-xs transition-all  ${isItemOpen && " rotate-180"}`}
                    />
                )}
            </p>

            {/* dropdown */}
            {isItemOpen && d.children && (
                <div className="  w-auto  flex-col gap-1   rounded-lg bg-white py-2   transition-all flex ">
                    {d.children.map((ch, i) => (
                        <Link
                            key={i}
                            href={ch.link ?? "#"}
                            className=" flex cursor-pointer items-center  py-2 pl-6 pr-8  hover:text-teal-500"
                        >
                            {/* item */}
                            <span className="whitespace-nowrap pl-3 ">{ch.label}</span>
                        </Link>
                    ))}
                </div>
            )}
        </div>
    );
}

export default Header