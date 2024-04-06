import { render } from '@testing-library/react';

import { UiImage } from '@/components';

describe('UiImage', () => {
  it('renders the image with the correct props', () => {
    const { getByAltText } = render(
      <UiImage
        alt="My Image"
        src="/my-image.jpg"
        sx={{ width: '100px', height: '100px' }}
      />
    );

    const image: HTMLElement = getByAltText('My Image');
    expect(image).toBeInTheDocument();
  });
});
