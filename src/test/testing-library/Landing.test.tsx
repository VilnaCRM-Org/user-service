import { render } from '@testing-library/react';
import { DynamicOptions, Loader } from 'next/dynamic';
import React from 'react';
import '@testing-library/jest-dom';

import Landing from '../../features/landing/components/Landing/Landing';

jest.mock('next/head', () => ({
  __esModule: true,
  default: ({ children }: { children: Array<React.ReactElement> }): React.JSX.Element => (
    <div>{children}</div>
  ),
}));

jest.mock('next/dynamic', () => ({
  __esModule: true,
  default: (...props: never): never => {
    const dynamicModule: typeof import('next/dynamic') = jest.requireActual('next/dynamic');
    const dynamicActualComp: <P = object>(
      dynamicOptions: DynamicOptions<P> | Loader<P>,
      options?: DynamicOptions<P>
    ) => React.ComponentType<P> = dynamicModule.default;
    const RequiredComponent: React.ComponentType<object> = dynamicActualComp(props[0]);
    const requiredMock: (mock: never) => never = mock => mock;
    // @ts-expect-error no jest types
    const test: never = RequiredComponent.preload
      ? // @ts-expect-error no jest types
        RequiredComponent.preload()
      : // @ts-expect-error no jest types
        RequiredComponent.render.preload();
    requiredMock(test);
    // @ts-expect-error no jest types
    return RequiredComponent;
  },
}));

jest.mock('../../features/landing/components/Header/Header', () =>
  jest.fn(() => <div data-testid="header">Header</div>)
);
jest.mock('../../features/landing/components/BackgroundImages/BackgroundImages', () =>
  jest.fn(() => <div data-testid="background-images">BackgroundImages</div>)
);
jest.mock('../../features/landing/components/AboutUs/AboutUs', () =>
  jest.fn(() => <div data-testid="about-us">AboutUs</div>)
);
jest.mock('../../features/landing/components/WhyUs/WhyUs', () =>
  jest.fn(() => <div data-testid="why-us">WhyUs</div>)
);
jest.mock('../../features/landing/components/ForWhoSection/ForWhoSection', () =>
  jest.fn(() => <div data-testid="for-who-section">ForWhoSection</div>)
);
jest.mock('../../features/landing/components/Possibilities/Possibilities', () =>
  jest.fn(() => <div data-testid="possibilities">Possibilities</div>)
);
jest.mock('../../features/landing/components/AuthSection/AuthSection', () =>
  jest.fn(() => <div data-testid="auth-section">AuthSection</div>)
);
jest.mock('../../components/UiFooter/UiFooter', () =>
  jest.fn(() => <div data-testid="ui-footer">UiFooter</div>)
);

const metaAttributesSelector: string =
  'meta[name="description"][content="The first Ukrainian open source CRM"]';
const boxElementClass: string = '.MuiBox-root';
const logoName: string = 'VilnaCRM';
const positionRelativeStyle: string = 'position: relative';

describe('Landing', () => {
  it('render all components', () => {
    const { getByTestId } = render(<Landing />);

    expect(getByTestId('header')).toBeInTheDocument();
    expect(getByTestId('background-images')).toBeInTheDocument();
    expect(getByTestId('about-us')).toBeInTheDocument();
    expect(getByTestId('why-us')).toBeInTheDocument();
    expect(getByTestId('for-who-section')).toBeInTheDocument();
    expect(getByTestId('possibilities')).toBeInTheDocument();
    expect(getByTestId('auth-section')).toBeInTheDocument();
    expect(getByTestId('ui-footer')).toBeInTheDocument();
  });

  it('render container correctly', () => {
    const { container } = render(<Landing />);

    const mainContainer: HTMLElement | null = container.querySelector(boxElementClass);

    expect(mainContainer).toHaveStyle(positionRelativeStyle);
  });

  it('render right title and metadata', async () => {
    const { getByText, container } = render(<Landing />);

    const titleElement: HTMLElement = getByText(logoName);
    const metaElement: HTMLElement | null = container.querySelector(metaAttributesSelector);

    expect(titleElement).toHaveTextContent(logoName);
    expect(metaElement).toBeInTheDocument();
  });
});
