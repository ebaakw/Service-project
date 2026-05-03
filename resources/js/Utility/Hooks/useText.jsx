
export const limitTextLength = (text, maxLength = 20) => {
    if (text.length <= maxLength) {
        return text;
    }

    // Find the last space within the maxLength
    const trimmedText = text.slice(0, maxLength + 1);
    const lastSpaceIndex = trimmedText.lastIndexOf(' ');

    // Cut off at the last space if found, otherwise cut at maxLength
    return (lastSpaceIndex > 0 ? trimmedText.slice(0, lastSpaceIndex) : trimmedText.slice(0, maxLength)) + '...';
}
