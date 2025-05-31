"use client"
import React from 'react';
import { Save, Clock, Calendar1, EyeOff, Eye, ToggleLeft, ToggleRight, SlidersVertical, DownloadIcon, Link2 } from 'lucide-react';
import { ResultItem } from '../_utils/apiResolveHistory';
import ModalImage from 'react-modal-image';

interface ResultCardProps {
    data: ResultItem;
    isInitialThumbnailHidden: boolean;
    isInitialSummaryShow: boolean;
    index?: number;
    dataIndex?: number;
}

interface ButtonElementProps {
    svgelement?: React.ReactNode;
    label: string;
    onclick?: () => void;
    disabled?: boolean;
    className?: string;
}

const ButtonElement = ({ svgelement, label, onclick, disabled, className }: ButtonElementProps) => {
    return (
        <button
            type="button"
            disabled={disabled}
            onClick={onclick}
            className={`${className} text-wrap justify-center text-center flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white`}
        >
            {svgelement}
            {label}
        </button>
    );
};

const ResultCard: React.FC<ResultCardProps> = ({ data, isInitialSummaryShow, isInitialThumbnailHidden, index, dataIndex }) => {
    const [isThumbnailHidden, setThumbnailHidden] = React.useState(isInitialThumbnailHidden);
    const [isSummaryShow, setToggleSummary] = React.useState(isInitialSummaryShow);
    const [isShowVideo, setToggleShowVideo] = React.useState(false);

    React.useEffect(() => {
        setThumbnailHidden(isInitialThumbnailHidden);
    }, [isInitialThumbnailHidden]);
    React.useEffect(() => {
        setToggleSummary(isInitialSummaryShow);
    }, [isInitialSummaryShow]);

    return (
        <article data-index={index} key={index} className="bg-white flex flex-col justify-between relative mb-4 overflow-hidden rounded-xl border text-gray-700 shadow-md duration-500 ease-in-out hover:shadow-xl">
            <div className={isThumbnailHidden ? "invisible max-h-3" : ""}>
                {isShowVideo ? <video className="h-full max-h-96 w-full max-w-96 m-auto rounded-lg" controls> <source src={data.video_src} type="video/mp4" /> Your browser does not support the video tag. </video> :
                    <ModalImage
                        small={isSummaryShow ? (data.summary_url || "https://demofree.sirv.com/nope-not-here.jpg") : data.thumbnail_url || "https://demofree.sirv.com/nope-not-here.jpg"}
                        large={isSummaryShow ? (data.summary_url || "https://demofree.sirv.com/nope-not-here.jpg") : data.thumbnail_url || "https://demofree.sirv.com/nope-not-here.jpg"}
                        alt={data.title}
                        className="max-h-64 max-w-64 m-auto rounded-lg"
                    />
                }
            </div>

            <div className="p-4">
                <div className="pb-4">
                    <a href="#" className="text-lg hover:text-green-600 font-medium duration-500 ease-in-out">{data.title}</a>
                </div>

                <ul className="box-border flex list-none items-center border-t border-b border-solid border-gray-200 px-0 py-2">
                    <li className="mr-4 flex items-center text-left">
                        <Save className="mr-1 w-3 h-3 text-2xl text-teal-600"></Save>
                        <span className="text-xs">{data.size}</span>
                    </li>

                    <li className="mr-4 flex items-center text-left">
                        <Clock className="mr-1 w-3 h-3 text-2xl text-teal-600"></Clock>
                        <span className="text-xs">{data.length}</span>
                    </li>

                    <li className="flex items-center text-left">
                        <Calendar1 className="mr-1 w-3 h-3 text-2xl text-teal-600"></Calendar1>
                        <span className="text-xs">{data.fetched_at}</span>
                    </li>
                </ul>
                <ul className='pt-2'>
                    <li className="flex items-center text-left">
                        <Link2 className="mr-1 w-3 h-3 text-2xl text-teal-600" />
                        <a href={data.video_url} target="_blank" className="text-xs">{data.video_url}</a>
                    </li>
                </ul>

                <div className="flex flex-col rounded-md shadow-xs pt-4" role="group">
                    {isThumbnailHidden && (
                        <ButtonElement
                            svgelement={<Eye className="w-3 h-3 me-2" />}
                            label="Show Thumbnail"
                            onclick={() => setThumbnailHidden(!isThumbnailHidden)}
                        />
                    )}
                    {!isThumbnailHidden && (
                        <ButtonElement
                            svgelement={<EyeOff className="w-3 h-3 me-2" />}
                            label="Hide Thumbnail"
                            onclick={() => setThumbnailHidden(!isThumbnailHidden)}
                        />
                    )}
                    {isSummaryShow && (
                        <ButtonElement
                            svgelement={<ToggleLeft className="w-3 h-3 me-2" />}
                            label="Toggle Thumbnail"
                            onclick={() => setToggleSummary(!isSummaryShow)}
                        />
                    )}
                    {!isSummaryShow && (
                        <ButtonElement
                            svgelement={<ToggleRight className="w-3 h-3 me-2" />}
                            label="Toggle Summary"
                            onclick={() => setToggleSummary(!isSummaryShow)}
                        />
                    )}
                    <div className='relative'>
                        <div className='flex flex-row flex-wrap items-center justify-center-safe mt-2 rounded-md transition-all w-full'>
                            <ButtonElement
                                label="Fetch"
                                onclick={() => (console.log("Re-Fetch"))}
                                className='w-1/3'
                            />
                            <ButtonElement
                                label="Download"
                                onclick={() => { window.open(data.video_src, "_blank") }}
                                className='w-1/3'
                            />
                            {!isShowVideo && (
                                <ButtonElement
                                    label="View Video"
                                    onclick={() => (setToggleShowVideo(true))}
                                    className='w-1/3'
                                />
                            )}
                            {isShowVideo && (
                                <ButtonElement
                                    label="Hide Video"
                                    onclick={() => (setToggleShowVideo(false))}
                                    className='w-1/3'
                                />
                            )}
                        </div>
                    </div>

                </div>
            </div>
        </article>
    )
}

export default ResultCard