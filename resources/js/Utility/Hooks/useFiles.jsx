// Function to fetch file from URL
export const getFileFromUrl = async (url, filename) => {
    const response = await fetch(url, { mode: 'cors' });

    if (!response.ok) {
        throw new Error(`Failed to fetch file: ${response.statusText}`);
    }

    const blob = await response.blob();
    // Convert the blob to a File object
    return new File([blob], filename, { type: blob.type });
};
