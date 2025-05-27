'use client';
import { useState } from 'react';
import { Search as SearchIconLucide, ChevronDown } from 'lucide-react';

const options = [
    {
        label: 'Files',
        value: 'Search Files',
    },
    {
        label: 'Folders',
        value: 'Search Folder',
    },
];

interface StatusDropdownProps {
    onChange: (newSearchJenis: string) => void;
    isLoadings?: boolean;
    buttonKeyword?: string;
    handleSubmit: (e: React.FormEvent) => void;
}

export default function StatusDropdown({ onChange, isLoadings, buttonKeyword, handleSubmit }: StatusDropdownProps) {
    const [selected, setSelected] = useState(options[0]);
    const [open, setOpen] = useState(false);

    interface Option {
        label: string;
        value: string;
    }

    const handleSelect = (option: Option) => {
        setSelected(option);
        setOpen(false);
        if (onChange) onChange(option.label);
    };

    return (
        <div className="relative inline-block text-left">
            <div className="inline-flex rounded-md shadow-xs" role="group">
                <button
                id='fetchButton'
                disabled={isLoadings || !buttonKeyword}
                onClick={handleSubmit}
                 className="focus-visible:border-ring focus-visible:ring-ring/50 whitespace-nowrap' } inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-s-lg bg-teal-500 px-4 py-2 text-sm text-white shadow-xs transition-all outline-none hover:bg-teal-600 focus-visible:ring-[3px] disabled:pointer-events-none disabled:opacity-80 has-[>svg]:px-3 dark:bg-teal-700 dark:hover:bg-teal-600 border-r border-gray-400">
                    <SearchIconLucide className='w-4 h-4'/>
                    {isLoadings ? "Memproses" : selected.label}
                </button>
                <button type='button' id='changeBulkType' onClick={() => setOpen(!open)} className="focus-visible:border-ring focus-visible:ring-ring/50 whitespace-nowrap' } inline-flex h-9 shrink-0 items-center justify-center rounded-e-lg bg-teal-500 text-sm text-white shadow-xs transition-all outline-none hover:bg-teal-600 focus-visible:ring-[3px] disabled:pointer-events-none pr-3 disabled:opacity-50 dark:bg-teal-700 dark:hover:bg-teal-600">
                    <ChevronDown className="ml-2 w-4 h-4" />
                </button>
            </div>
            {open && (
                <div className="absolute top-full left-0 mt-2 w-36 flex-col bg-white shadow-lg rounded-md transition-all flex py-3">
                    <div className="">
                        {options.map((option) => (
                            <button
                                key={option.value}
                                onClick={() => {
                                    handleSelect(option)
                                }}
                                className={`block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100'
                                    }`}
                            >

                                <div className="flex justify-between items-center mb-1">
                                    <span className="font-medium">{option.label}</span>
                                    {selected.value === option.value}
                                </div>
                            </button>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
