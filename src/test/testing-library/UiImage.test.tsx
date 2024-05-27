import { render } from '@testing-library/react';

import { UiImage } from '@/components';

import { testImg, testText } from './constants';

describe('UiImage', () => {
  it('renders the image with the correct props', () => {
    const { getByAltText } = render(
      <UiImage alt={testText} src={testImg} sx={{ width: '100px', height: '100px' }} />
    );

    const image: HTMLElement = getByAltText(testText);
    expect(image).toBeInTheDocument();
  });
});
