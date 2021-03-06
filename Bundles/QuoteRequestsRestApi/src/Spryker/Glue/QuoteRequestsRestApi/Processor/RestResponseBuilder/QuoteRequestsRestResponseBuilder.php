<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\QuoteRequestsRestApi\Processor\RestResponseBuilder;

use ArrayObject;
use Generated\Shared\Transfer\MessageTransfer;
use Generated\Shared\Transfer\QuoteRequestCollectionTransfer;
use Generated\Shared\Transfer\QuoteRequestTransfer;
use Generated\Shared\Transfer\RestErrorMessageTransfer;
use Generated\Shared\Transfer\RestQuoteRequestsAttributesTransfer;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceBuilderInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;
use Spryker\Glue\QuoteRequestsRestApi\Processor\Mapper\QuoteRequestMapperInterface;
use Spryker\Glue\QuoteRequestsRestApi\QuoteRequestsRestApiConfig;
use Symfony\Component\HttpFoundation\Response;

class QuoteRequestsRestResponseBuilder implements QuoteRequestsRestResponseBuilderInterface
{
    /**
     * @var \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceBuilderInterface
     */
    protected $restResourceBuilder;

    /**
     * @var \Spryker\Glue\QuoteRequestsRestApi\Processor\Mapper\QuoteRequestMapperInterface
     */
    protected $quoteRequestMapper;

    /**
     * @var \Spryker\Glue\QuoteRequestsRestApi\QuoteRequestsRestApiConfig
     */
    protected $quoteRequestsRestApiConfig;

    /**
     * @param \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceBuilderInterface $restResourceBuilder
     * @param \Spryker\Glue\QuoteRequestsRestApi\Processor\Mapper\QuoteRequestMapperInterface $quoteRequestMapper
     * @param \Spryker\Glue\QuoteRequestsRestApi\QuoteRequestsRestApiConfig $quoteRequestsRestApiConfig
     */
    public function __construct(
        RestResourceBuilderInterface $restResourceBuilder,
        QuoteRequestMapperInterface $quoteRequestMapper,
        QuoteRequestsRestApiConfig $quoteRequestsRestApiConfig
    ) {
        $this->restResourceBuilder = $restResourceBuilder;
        $this->quoteRequestMapper = $quoteRequestMapper;
        $this->quoteRequestsRestApiConfig = $quoteRequestsRestApiConfig;
    }

    /**
     * @param \Generated\Shared\Transfer\MessageTransfer[]|\ArrayObject $errors
     *
     * @return \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface
     */
    public function createFailedErrorResponse(ArrayObject $errors): RestResponseInterface
    {
        $restResponse = $this->createRestResponse();

        foreach ($errors as $messageTransfer) {
            $restResponse->addError(
                $this->mapQuoteRequestErrorMessageTransferToRestErrorMessageTransfer(
                    $messageTransfer,
                    new RestErrorMessageTransfer()
                )
            );
        }

        return $restResponse;
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteRequestTransfer $quoteRequestTransfer
     *
     * @return \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface
     */
    public function createQuoteRequestRestResponse(QuoteRequestTransfer $quoteRequestTransfer): RestResponseInterface
    {
        return $this->createRestResponse()->addResource($this->createQuoteRequestResource($quoteRequestTransfer));
    }

    /**
     * @return \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface
     */
    public function createCartNotFoundErrorResponse(): RestResponseInterface
    {
        $restErrorTransfer = (new RestErrorMessageTransfer())
            ->setCode(QuoteRequestsRestApiConfig::RESPONSE_CODE_CART_NOT_FOUND)
            ->setStatus(Response::HTTP_NOT_FOUND)
            ->setDetail(QuoteRequestsRestApiConfig::RESPONSE_DETAIL_CART_NOT_FOUND);

        return $this->restResourceBuilder->createRestResponse()->addError($restErrorTransfer);
    }

    /**
     * @return \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface
     */
    public function createCartIsEmptyErrorResponse(): RestResponseInterface
    {
        $restErrorTransfer = (new RestErrorMessageTransfer())
            ->setCode(QuoteRequestsRestApiConfig::RESPONSE_CODE_CART_IS_EMPTY)
            ->setStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->setDetail(QuoteRequestsRestApiConfig::RESPONSE_DETAIL_CART_IS_EMPTY);

        return $this->restResourceBuilder->createRestResponse()->addError($restErrorTransfer);
    }

    /**
     * @return \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface
     */
    public function createQuoteRequestNotFoundErrorResponse(): RestResponseInterface
    {
        $restErrorTransfer = (new RestErrorMessageTransfer())
            ->setCode(QuoteRequestsRestApiConfig::RESPONSE_CODE_QUOTE_REQUEST_NOT_FOUND)
            ->setStatus(Response::HTTP_NOT_FOUND)
            ->setDetail(QuoteRequestsRestApiConfig::RESPONSE_DETAIL_QUOTE_REQUEST_NOT_FOUND);

        return $this->restResourceBuilder->createRestResponse()->addError($restErrorTransfer);
    }

    /**
     * @return \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface
     */
    public function createQuoteRequestReferenceMissingErrorResponse(): RestResponseInterface
    {
        $restErrorMessageTransfer = (new RestErrorMessageTransfer())
            ->setCode(QuoteRequestsRestApiConfig::RESPONSE_CODE_QUOTE_REQUEST_REFERENCE_MISSING)
            ->setStatus(Response::HTTP_BAD_REQUEST)
            ->setDetail(QuoteRequestsRestApiConfig::RESPONSE_DETAIL_QUOTE_REQUEST_REFERENCE_MISSING);

        return $this->restResourceBuilder
            ->createRestResponse()
            ->addError($restErrorMessageTransfer);
    }

    /**
     * @return \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface
     */
    public function createNoContentResponse(): RestResponseInterface
    {
        return $this->restResourceBuilder
            ->createRestResponse()
            ->setStatus(Response::HTTP_NO_CONTENT);
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteRequestCollectionTransfer $quoteRequestCollectionTransfer
     *
     * @return \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface
     */
    public function createQuoteRequestCollectionRestResponse(
        QuoteRequestCollectionTransfer $quoteRequestCollectionTransfer
    ): RestResponseInterface {
        $totalItems = 0;
        $limit = 0;
        if ($quoteRequestCollectionTransfer->getPagination()) {
            $totalItems = $quoteRequestCollectionTransfer->getPagination()->getNbResults() ?? 0;
            $limit = $quoteRequestCollectionTransfer->getPagination()->getMaxPerPage() ?? 0;
        }

        $restResponse = $this->restResourceBuilder->createRestResponse($totalItems, $limit);

        foreach ($quoteRequestCollectionTransfer->getQuoteRequests() as $quoteRequestTransfer) {
            $restResponse->addResource($this->createQuoteRequestResource($quoteRequestTransfer));
        }

        return $restResponse;
    }

    /**
     * @return \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface
     */
    protected function createRestResponse(): RestResponseInterface
    {
        return $this->restResourceBuilder->createRestResponse();
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteRequestTransfer $quoteRequestTransfer
     *
     * @return \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface
     */
    protected function createQuoteRequestResource(QuoteRequestTransfer $quoteRequestTransfer): RestResourceInterface
    {
        $cartResource = $this->restResourceBuilder->createRestResource(
            QuoteRequestsRestApiConfig::RESOURCE_QUOTE_REQUESTS,
            $quoteRequestTransfer->getQuoteRequestReference(),
            $this->quoteRequestMapper->mapQuoteRequestTransferToRestQuoteRequestsAttributesTransfer(
                $quoteRequestTransfer,
                new RestQuoteRequestsAttributesTransfer()
            )
        );

        $cartResource->setPayload($quoteRequestTransfer);

        return $cartResource;
    }

    /**
     * @param \Generated\Shared\Transfer\MessageTransfer $messageTransfer
     * @param \Generated\Shared\Transfer\RestErrorMessageTransfer $restErrorMessageTransfer
     *
     * @return \Generated\Shared\Transfer\RestErrorMessageTransfer
     */
    protected function mapQuoteRequestErrorMessageTransferToRestErrorMessageTransfer(
        MessageTransfer $messageTransfer,
        RestErrorMessageTransfer $restErrorMessageTransfer
    ): RestErrorMessageTransfer {
        $errorMapping = $this->quoteRequestsRestApiConfig->getErrorMapping();

        if (!isset($errorMapping[$messageTransfer->getValue()])) {
            return $restErrorMessageTransfer
                ->setCode(QuoteRequestsRestApiConfig::RESPONSE_CODE_QUOTE_REQUEST_VALIDATION)
                ->setStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->setDetail(QuoteRequestsRestApiConfig::RESPONSE_DETAIL_QUOTE_REQUEST_VALIDATION);
        }

        return $restErrorMessageTransfer->fromArray($errorMapping[$messageTransfer->getValue()], true);
    }
}
