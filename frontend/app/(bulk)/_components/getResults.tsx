import React from 'react';
import ResultCard from './ResultCard';
import FolderCard from './folderCard'

const GetResults = ({ urls }: { urls: any[], }) => {
  if (!urls || urls.length === 0) {
    return null;
  }

  return (
    <div className="space-y-2">
      {urls.map((video_src, index) => (
        <div
          key={index}
          className="p-2 bg-gray-100 rounded-md shadow-md"
        >
          {video_src.type === "dood_folder" ? ( <FolderCard urls={video_src.url} indexNum={index} /> ) : ( <ResultCard urls={video_src.url} indexNum={index} /> ) }

        </div>
      ))}
    </div>
  );
};

export default GetResults;